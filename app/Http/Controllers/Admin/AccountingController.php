<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\LessonUser;
use App\Models\UserPackage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AccountingController extends Controller
{
    /**
     * Pagina unica Contabilità (server render + primi KPI).
     * La view sarà creata nello step successivo.
     */
    public function show(Request $request)
    {
        [$from, $to] = $this->parseRange($request);
        $defaultLessonPrice = (float) config('billing.lesson_price', 0.0);

        // Base query: lezioni pagate "a lezione" (no pacchetto)
        $lessonsBase = LessonUser::query()
            ->active()
            ->whereNull('user_package_id')
            ->whereNotNull('paid_at')
            ->whereBetween(DB::raw('DATE(paid_at)'), [$from->toDateString(), $to->toDateString()]);

        // Totale lezioni
        $totLessons = (clone $lessonsBase)->selectRaw('SUM(COALESCE(lesson_price, ?)) as s', [$defaultLessonPrice])->value('s') ?? 0.0;

        // Breakdown per operatore (paid_to_user_id)
        $byOperator = (clone $lessonsBase)
            ->selectRaw('paid_to_user_id, SUM(COALESCE(lesson_price, ?)) as total', [$defaultLessonPrice])
            ->groupBy('paid_to_user_id')
            ->with(['paidTo:id,first_name,last_name'])
            ->get();

        // Serie giornaliera lezioni
        $seriesLessons = (clone $lessonsBase)
            ->selectRaw('DATE(paid_at) as d, SUM(COALESCE(lesson_price, ?)) as total', [$defaultLessonPrice])
            ->groupBy('d')
            ->orderBy('d')
            ->pluck('total', 'd');

        // Pacchetti: somma prezzi dal listino corrente (storico non necessario)
        $packagesBase = UserPackage::query()
            ->whereBetween(DB::raw('DATE(purchased_at)'), [$from->toDateString(), $to->toDateString()])
            ->join('packages', 'user_packages.package_id', '=', 'packages.id');

        $totPackages = (clone $packagesBase)->sum('packages.price') ?? 0.0;

        $seriesPackages = (clone $packagesBase)
            ->selectRaw('DATE(user_packages.purchased_at) as d, SUM(packages.price) as total')
            ->groupBy('d')
            ->orderBy('d')
            ->pluck('total', 'd');

        // Lezioni svolte (starts_at nel range) non ancora pagate e non coperte da pacchetto
        $unpaidLessons = LessonUser::query()
            ->active()
            ->whereNull('user_package_id')
            ->whereNull('paid_at')
            ->whereHas('lesson', function ($q) use ($from, $to) {
                $q->whereBetween(DB::raw('DATE(starts_at)'), [$from->toDateString(), $to->toDateString()]);
            })
            ->with([
                'lesson:id,starts_at,operator_id',
                'lesson.operator:id,first_name,last_name',
                'user:id,first_name,last_name,email',
            ])
            ->orderByDesc('id')
            ->get();

        $totDaily = (float) $totLessons + (float) $totPackages;

        return view('admin.accounting.show', [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'totLessons' => (float) $totLessons,
            'totPackages' => (float) $totPackages,
            'byOperator' => $byOperator,
            'seriesLessons' => $seriesLessons,
            'seriesPackages' => $seriesPackages,
            'unpaidLessons' => $unpaidLessons,
            'defaultLessonPrice' => $defaultLessonPrice,
            'totDaily' => $totDaily, // << NEW
        ]);

    }

    /**
     * Endpoint JSON per Chart.js / refresh KPI.
     */
    public function data(Request $request)
    {
        [$from, $to] = $this->parseRange($request);
        $defaultLessonPrice = (float) config('billing.lesson_price', 0.0);

        $lessonsBase = LessonUser::query()
            ->active()
            ->whereNull('user_package_id')
            ->whereNotNull('paid_at')
            ->whereBetween(DB::raw('DATE(paid_at)'), [$from->toDateString(), $to->toDateString()]);

        $totLessons = (clone $lessonsBase)
            ->selectRaw('SUM(COALESCE(lesson_price, ?)) as s', [$defaultLessonPrice])
            ->value('s') ?? 0.0;

        $byOperator = (clone $lessonsBase)
            ->selectRaw('paid_to_user_id, SUM(COALESCE(lesson_price, ?)) as total', [$defaultLessonPrice])
            ->groupBy('paid_to_user_id')
            ->with(['paidTo:id,first_name,last_name'])
            ->get()
            ->map(function ($row) {
                return [
                    'operator_id' => $row->paid_to_user_id,
                    'operator' => $row->paidTo ? ($row->paidTo->first_name . ' ' . $row->paidTo->last_name) : '—',
                    'total' => (float) $row->total,
                ];
            });

        $seriesLessons = (clone $lessonsBase)
            ->selectRaw('DATE(paid_at) as d, SUM(COALESCE(lesson_price, ?)) as total', [$defaultLessonPrice])
            ->groupBy('d')
            ->orderBy('d')
            ->pluck('total', 'd');

        // Pacchetti
        $packagesBase = UserPackage::query()
            ->whereBetween(DB::raw('DATE(purchased_at)'), [$from->toDateString(), $to->toDateString()])
            ->join('packages', 'user_packages.package_id', '=', 'packages.id');

        $totPackages = (clone $packagesBase)->sum('packages.price') ?? 0.0;

        $seriesPackages = (clone $packagesBase)
            ->selectRaw('DATE(user_packages.purchased_at) as d, SUM(packages.price) as total')
            ->groupBy('d')
            ->orderBy('d')
            ->pluck('total', 'd');

        // NEW: serie giornaliere per operatore (quanto è stato pagato a ciascuno per giorno)
        $rawByOpDaily = (clone $lessonsBase)
            ->selectRaw('DATE(paid_at) as d, paid_to_user_id, SUM(COALESCE(lesson_price, ?)) as total', [$defaultLessonPrice])
            ->groupBy('d', 'paid_to_user_id')
            ->with(['paidTo:id,first_name,last_name'])
            ->orderBy('d')
            ->get();

        $seriesByOperator = [];
        foreach ($rawByOpDaily as $row) {
            $opId = $row->paid_to_user_id ?: 0;
            if (!isset($seriesByOperator[$opId])) {
                $label = $row->paidTo ? ($row->paidTo->first_name . ' ' . $row->paidTo->last_name) : '—';
                $seriesByOperator[$opId] = ['label' => $label, 'series' => []];
            }
            $seriesByOperator[$opId]['series'][$row->d] = (float) $row->total;
        }

        $unpaidCount = LessonUser::query()
            ->active()
            ->whereNull('user_package_id')
            ->whereNull('paid_at')
            ->whereHas('lesson', function ($q) use ($from, $to) {
                $q->whereBetween(DB::raw('DATE(starts_at)'), [$from->toDateString(), $to->toDateString()]);
            })
            ->count();

        return response()->json([
            'ok' => true,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'totLessons' => (float) $totLessons,
            'totPackages' => (float) $totPackages,
            'seriesLessons' => $seriesLessons,
            'seriesPackages' => $seriesPackages,
            'byOperator' => $byOperator, // lista totale periodo (resta com’era)
            'seriesByOperator' => $seriesByOperator, // << NEW: serie giornaliere per chart
            'unpaidCount' => $unpaidCount,
        ]);
    }


    /**
     * Parsing e normalizzazione del range date.
     * Default: oggi (Europe/Rome).
     */
    private function parseRange(Request $request): array
    {
        $tz = config('app.timezone', 'Europe/Rome');

        $from = $request->query('from');
        $to = $request->query('to');

        if (!$from && !$to) {
            $from = Carbon::now($tz)->toDateString();
            $to = $from;
        }

        $fromC = Carbon::parse($from, $tz)->startOfDay();
        $toC = Carbon::parse($to, $tz)->endOfDay();

        if ($toC->lt($fromC)) {
            [$fromC, $toC] = [$toC->copy()->startOfDay(), $fromC->copy()->endOfDay()];
        }

        return [$fromC, $toC];
    }
}

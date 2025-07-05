@extends('layouts.app')

@section('content')
    <div class="container py-5">
        <h2 class="mb-4">🧪 Local Dev Checklist</h2>

        <ul class="list-group">
            <li class="list-group-item">
                ✅ <code>composer install</code>
            </li>
            <li class="list-group-item">
                ✅ <code>npm install</code>
            </li>
            <li class="list-group-item">
                ✅ <code>cp .env.example .env</code>
            </li>
            <li class="list-group-item">
                ✅ <code>php artisan key:generate</code>
            </li>
            <li class="list-group-item">
                ✅ <code>php artisan migrate --seed</code> <small class="text-muted">(if needed)</small>
            </li>
            <li class="list-group-item">
                ✅ <code>npm run dev</code> <small class="text-muted">(or <code>build</code> for production)</small>
            </li>
            <li class="list-group-item">
                ✅ <code>php artisan serve</code>
            </li>
        </ul>

        <div class="mt-4">
            <strong>Env Notes:</strong>
            <ul>
                <li>Edit <code>.env</code> to set <code>APP_NAME</code>, <code>APP_URL</code>, etc.</li>
                <li>Confirm DB credentials & mail settings if needed</li>
            </ul>
        </div>

        <hr class="my-5">

        <h3>🔍 Asset Tests</h3>

        <div class="mb-3">
            <strong>Bootstrap CSS Test:</strong><br>
            <button class="btn btn-primary">This should be a blue Bootstrap button</button>
        </div>

        <div class="mb-3">
            <strong>Bootstrap JS Test (Collapse):</strong><br>
            <p>
                <a class="btn btn-secondary" data-bs-toggle="collapse" href="#collapseTest" role="button"
                    aria-expanded="false" aria-controls="collapseTest">
                    Toggle Collapsible
                </a>
            </p>
            <div class="collapse" id="collapseTest">
                <div class="card card-body">
                    ✅ If you're reading this, Bootstrap JS is working!
                </div>
            </div>
        </div>

        <div class="mb-3">
            <strong>Font Awesome Icon Test:</strong><br>
            <i class="fas fa-check-circle fa-2x text-success"></i> If you see a green check icon, Font Awesome is working!
        </div>

    </div>
@endsection

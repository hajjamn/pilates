@extends('layouts.app')

@section('content')
    <div class="container py-5">

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

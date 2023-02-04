@extends('adminlte::page')

@section('title', 'Alertas')
@section('plugins.Select2', true)

@section('content_header')
    <h1>Alertas</h1>
@stop

@section('content')
    <section class="content container-fluid">
        <div class="">
            <div class="col-md-12">

                @includeif('partials.errors')

                <div class="card card-default">
                    <div class="card-header">
                        <span class="card-title">Actualizar Alerta</span>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('alertas.update', $alerta->id) }}"  role="form" enctype="multipart/form-data">
                            {{ method_field('PATCH') }}
                            @csrf

                            @include('alerta.form')

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('css')
@stop

@section('js') 
<script>
    $(document).ready(function() {
    $('#procesos').select2();
});
</script>  
 
@stop
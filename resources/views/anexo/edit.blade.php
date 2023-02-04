@extends('adminlte::page')

@section('title', 'Anexos')
@section('plugins.Select2', true)

@section('content_header')
    <h1>Anexos</h1>
@stop

@section('content')
    <section class="content container-fluid">
        <div class="">
            <div class="col-md-12">

                @includeif('partials.errors')

                <div class="card card-default">
                    <div class="card-header">
                        <span class="card-title">Actualizar Anexo</span>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('anexos.update', $anexo->id) }}"  role="form" enctype="multipart/form-data">
                            {{ method_field('PATCH') }}
                            @csrf

                            @include('anexo.form')

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
<script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>  
@stop
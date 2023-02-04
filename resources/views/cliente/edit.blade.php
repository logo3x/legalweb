@extends('adminlte::page')
@section('plugins.Select2', true)

@section('title', 'Clientes')

@section('content_header')
    <h1>Clientes</h1>
@stop

@section('content')
    <section class="content container-fluid">
        <div class="">
            <div class="col-md-12">

                @includeif('partials.errors')

                <div class="card card-default">
                    <div class="card-header">
                        <div class="row justify-content-md-center">
                            <div class="col col-lg-2"><br><br><br>
                              <h4>Actualizar Cliente</h4>
                            </div>
                            <div class="col-md-auto">
                              <lottie-player src="https://assets7.lottiefiles.com/private_files/lf30_hkykoylh.json"  background="transparent"  speed="1"  style="width: 150px; height: 150px;"  loop  autoplay></lottie-player>
                            </div>
                            
                          </div>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('clientes.update', $cliente->id) }}"  role="form" enctype="multipart/form-data">
                            {{ method_field('PATCH') }}
                            @csrf

                            @include('cliente.form')

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
    $('#ciudades').select2();
});
</script> 
<script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
@stop
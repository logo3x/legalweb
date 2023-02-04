@extends('adminlte::page')
@section('plugins.Select2', true)

@section('title', 'Actuaciones')

@section('content_header')
    <h1>Actuaciones</h1>
@stop

@section('content')
    <section class="content container-fluid">
        <div class="row">
            <div class="col-md-12">

                @includeif('partials.errors')

                <div class="card card-default">
                    <div class="card-header">
                        <div class="container">
                            <div class="row justify-content-md-center">
                              <div class="col col-lg-2"><br><br><br>
                                <h4>Nueva Actuacion</h4>
                              </div>
                              <div class="col-md-auto">
                                <lottie-player src="https://assets6.lottiefiles.com/packages/lf20_6dboqita.json"  background="transparent"  speed="1"  style="width: 150px; height: 150px;"  loop  autoplay></lottie-player>
                              </div>
                              
                            </div>
                          </div>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('actuaciones.store') }}"  role="form" enctype="multipart/form-data">
                            @csrf

                            @include('actuacione.form')

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

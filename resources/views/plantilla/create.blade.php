@extends('adminlte::page')
@section('plugins.Select2', true)

@section('title', 'Plantillas')


@section('content_header')
    <h1>Plantillas</h1>
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
                              <div class="col col-lg-3"><br><br><br>
                                <h4>Nuevo Plantilla</h4>
                              </div>
                              <div class="col-md-auto">
                                <lottie-player src="https://assets9.lottiefiles.com/private_files/lf30_8lo5f8ik.json"  background="transparent"  speed="1"  style="width: 150px; height: 150px;"  loop  autoplay></lottie-player>
                              </div>
                              
                            </div>
                          </div>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('plantillas.store') }}"  role="form" enctype="multipart/form-data">
                            @csrf

                            @include('plantilla.form')

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
    $('#tipoplantilla').select2();
});
</script>
<script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
@stop
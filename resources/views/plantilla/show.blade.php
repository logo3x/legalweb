@extends('adminlte::page')

@section('title', 'Plantillas')


@section('content_header')
    <h1>Plantillas</h1>
@stop

@section('content')
    <section class="content container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="float-left">
                            <span class="card-title">Ver Plantilla</span>
                        </div>
                        <div class="float-right">
                            <a class="btn btn-primary" href="{{ route('plantillas.index') }}"> Volver</a>
                        </div>
                    </div>

                    <div class="card-body ">

                        <div class="container text-center">
                            <div class="row shadow p-3 mb-5 bg-body rounded">
                                <div class="col-4">
                                    <div class="form-group">
                                        <strong>Tipo de plantilla:</strong><br>
                                        {{ $plantilla->tipoplantilla->nombre }}
                                    </div>
                                </div>
                                <div class="col-8">
                                    <div class="form-group">
                                        <strong>Nombre:</strong><br>
                                        {{ $plantilla->nombre }}
                                    </div>
                                </div>
                            </div>
                            <div class="row shadow p-3 mb-5 bg-body rounded">
                                <div class="col-12">
                                    <div class="form-group">
                                        <strong>Descripcion:</strong><br>
                                        {{ $plantilla->descripcion }}
                                    </div>
                                </div>
                            </div>
                            <div class="row shadow p-3 mb-5 bg-body rounded justify-content-center">
                                <div class="col-2">
                                    <div class="form-group">
                                        <strong>Plantilla:</strong><br>
                                        <a href="{{$plantilla->getFirstMediaUrl('anexo_plantillas')}}" target="_black">Descargar<lottie-player src="https://assets8.lottiefiles.com/packages/lf20_szdrhwiq.json"  background="transparent"  speed="1"  style="width: 100px; height: 100px;"  loop  autoplay></lottie-player></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                         
                        
                        
                        
                        
                        

                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('css')
@stop

@section('js')   
<script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
@stop
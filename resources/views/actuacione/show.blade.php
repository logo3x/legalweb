@extends('adminlte::page')

@section('title', 'Actuaciones')

@section('content_header')
    <h1>Actuaciones</h1>
@stop

@section('content')
    <section class="content container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="float-left">
                            <span class="card-title">Ver Actuaciones</span>
                        </div>
                        <div class="float-right">
                            <a class="btn btn-primary" href="{{ route('actuaciones.index') }}"> Volver</a>
                        </div>
                    </div>


                    <div class="container text-center">
                        <div class="row shadow p-3 mb-5 bg-body rounded">
                          <div class="col">
                            <div class="form-group">
                                <strong>Id Proceso:</strong><br>
                                {{ $actuacione->proceso->nombre }}
                            </div>
                          </div>
                          <div class="col">
                            <div class="form-group">
                                <strong>Nombre:</strong>
                                {{ $actuacione->nombre }}
                            </div>
                          </div>
                        </div>
                        <div class="row shadow p-3 mb-5 bg-body rounded">
                            <div class="col">
                                <div class="form-group">
                                    <strong>Descripcion:</strong>
                                    {{ $actuacione->descripcion }}
                                </div>
                            </div>
                        </div>
                        <div class="row shadow p-3 mb-5 bg-body rounded">
                            <div class="col">
                                <div class="form-group">
                                    <strong>Fecha:</strong>
                                    {{ $actuacione->fecha }}
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-group">
                                    <strong>Consecutivo:</strong>
                                    {{ $actuacione->consecutivo }}
                                </div>
                            </div>
                        </div>
                        <div class="row shadow p-3 mb-5 bg-body rounded justify-content-center">
                            <div class="col-2 ">
                                <div class="form-group">
                                    <strong>Plantilla:</strong><br>
                                    <a href="{{$actuacione->getFirstMediaUrl('anexo_actuaciones')}}" target="_black">Descargar<lottie-player src="https://assets6.lottiefiles.com/packages/lf20_jaxz4fko.json"  background="transparent"  speed="1"  style="width: 150px; height: 150px;"  loop  autoplay></lottie-player></a>
                                </div>
                            </div>
                          </div>
                      </div>

                    <div class="card-body">
                        
                        
                        
                        
                        
                        
                        

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

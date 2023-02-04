@extends('adminlte::page')

@section('title', 'Tramites')


@section('content_header')
    <h1>Tramites</h1>
@stop

@section('content')
    <section class="content container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="float-left">
                            <span class="card-title">Ver Tramite</span>
                        </div>
                        <div class="float-right">
                            <a class="btn btn-primary" href="{{ route('tramites.index') }}"> Volver</a>
                        </div>
                    </div>

                    <div class="card-body">

                        <div class="container text-center">
                            <div class="row shadow p-3 mb-5 bg-body rounded">
                              <div class="col">
                                <div class="form-group">
                                    <strong>Tipo de proceso:</strong><br>
                                    {{ $tramite->tipoproceso->nombre }}
                                </div>
                              </div>
                              <div class="col">
                                <div class="form-group">
                                    <strong>Nombre:</strong><br>
                                    {{ $tramite->nombre }}
                                </div>
                              </div>                              
                            </div>
                            <div class="row shadow p-3 mb-5 bg-body rounded">
                                <div class="col">
                                    <div class="form-group">
                                        <strong>Desc Esquema:</strong><br>
                                        {{ $tramite->desc_esquema }}
                                    </div>
                                </div>                                                            
                            </div>
                            <div class="row shadow p-3 mb-5 bg-body rounded">
                                <div class="col">
                                    <div class="form-group">
                                        <strong>Desc Tramite:</strong><br>
                                        {{ $tramite->desc_tramite }}
                                    </div>
                                </div>                                                            
                            </div>
                            <div class="row shadow p-3 mb-5 bg-body rounded justify-content-center">
                                <div class="col-2">
                                  
                                    <div class="form-group">
                                        <strong>Anexo Esquema:</strong><br>
                                        <a href="{{$tramite->getFirstMediaUrl('anexo_esquema')}}" target="_black">Descargar<lottie-player src="https://assets8.lottiefiles.com/packages/lf20_szdrhwiq.json"  background="transparent"  speed="1"  style="width: 100px; height: 100px;"  loop  autoplay></lottie-player></a>
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
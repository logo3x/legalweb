@extends('adminlte::page')

@section('title', 'Juzgados')

@section('content_header')
    <h1>Juzgados</h1>
@stop

@section('content')
    <section class="content container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="float-left">
                            <span class="card-title">Ver Juzgado</span>
                        </div>
                        <div class="float-right">
                            <a class="btn btn-primary" href="{{ route('juzgados.index') }}"> Volver</a>
                        </div>
                    </div>

                    <div class="card-body">
                        

                        <div class="container text-center">
                            <div class="row shadow p-3 mb-5 bg-body">
                              <div class="col">
                                <div class="form-group">
                                    <strong>Ciudad:</strong><br>
                                    {{ $juzgado->ciudade->nombre }}
                                </div>
                              </div>
                              <div class="col">
                                <div class="form-group">
                                    <strong>Nombre:</strong><br>
                                    {{ $juzgado->nombre }}
                                </div>
                              </div>
                            </div>
                            <div class="row shadow p-3 mb-5 bg-body">
                                <div class="col">
                                    <div class="form-group">
                                        <strong>Emails:      </strong>
                                        {{ $juzgado->email1 }}
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="form-group">
                                        
                                        {{ $juzgado->email2 }}
                                    </div>
                                </div>
                              </div>
                              <div class="row shadow p-3 mb-5 bg-body">
                                <div class="col">
                                    <div class="form-group">
                                        <strong>Telefonos:      </strong>  
                                        {{ $juzgado->tel1 }}
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="form-group">
                                       
                                        {{ $juzgado->tel2 }}
                                    </div>
                                </div>
                              </div>
                              <div class="row shadow p-3 mb-5 bg-body">
                                <div class="col">
                                    <div class="form-group">
                                        <strong>Juez:</strong><br>
                                        {{ $juzgado->juez }}
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="form-group">
                                        <strong>Secretario:</strong><br>
                                        {{ $juzgado->secretario }}
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

@extends('layouts.app')

@section('template_title')
    Alerta
@endsection

@section('content')
    <section class="content container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="float-left">
                            <span class="card-title">Ver Alerta</span>
                        </div>
                        <div class="float-right">
                            <a class="btn btn-primary" href="{{ route('alertas.index') }}"> Volver</a>
                        </div>
                    </div>

                    <div class="card-body">
                        
                        <div class="form-group">
                            <strong>Id Proceso:</strong>
                            {{ $alerta->id_proceso }}
                        </div>
                        <div class="form-group">
                            <strong>Nombre:</strong>
                            {{ $alerta->nombre }}
                        </div>
                        <div class="form-group">
                            <strong>Descripcion:</strong>
                            {{ $alerta->descripcion }}
                        </div>
                        <div class="form-group">
                            <strong>Estado:</strong>
                            {{ $alerta->estado }}
                        </div>
                        <div class="form-group">
                            <strong>Creacion:</strong>
                            {{ $alerta->creacion }}
                        </div>
                        <div class="form-group">
                            <strong>Vencimiento:</strong>
                            {{ $alerta->vencimiento }}
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
@stop
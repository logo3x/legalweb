@extends('adminlte::page')

@section('title', 'Anexos')

@section('content_header')
    <h1>Anexos</h1>
@stop
@section('content')
    <section class="content container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="float-left">
                            <span class="card-title">Ver Anexo</span>
                        </div>
                        <div class="float-right">
                            <a class="btn btn-primary" href="{{ route('anexos.index') }}"> Volver</a>
                        </div>
                    </div>

                    <div class="card-body">
                        
                        <div class="form-group">
                            <strong>Id Proceso:</strong>
                            {{ $anexo->id_proceso }}
                        </div>
                        <div class="form-group">
                            <strong>Nombre:</strong>
                            {{ $anexo->nombre }}
                        </div>
                        <div class="form-group">
                            <strong>Descripcion:</strong>
                            {{ $anexo->descripcion }}
                        </div>
                        <div class="form-group">
                            <strong>Anexo:</strong>
                            {{ $anexo->anexo }}
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
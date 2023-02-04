@extends('layouts.app')

@section('template_title')
    {{ $preliminare->name ?? 'Show Preliminare' }}
@endsection

@section('content')
    <section class="content container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="float-left">
                            <span class="card-title">Show Preliminare</span>
                        </div>
                        <div class="float-right">
                            <a class="btn btn-primary" href="{{ route('preliminares.index') }}"> Back</a>
                        </div>
                    </div>

                    <div class="card-body">
                        
                        <div class="form-group">
                            <strong>Fecha:</strong>
                            {{ $preliminare->fecha }}
                        </div>
                        <div class="form-group">
                            <strong>Id Cliente:</strong>
                            {{ $preliminare->id_cliente }}
                        </div>
                        <div class="form-group">
                            <strong>Relato:</strong>
                            {{ $preliminare->relato }}
                        </div>
                        <div class="form-group">
                            <strong>Gestion:</strong>
                            {{ $preliminare->gestion }}
                        </div>
                        <div class="form-group">
                            <strong>Des Gestion:</strong>
                            {{ $preliminare->des_gestion }}
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

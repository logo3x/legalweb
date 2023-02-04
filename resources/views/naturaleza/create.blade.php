@extends('adminlte::page')

@section('title', 'Tramites')

@section('content_header')
    <h1>Naturalezas de Procesos</h1>
@stop

@section('content')
    <section class="content container-fluid">
        <div class="row">
            <div class="col-md-12">

                @includeif('partials.errors')

                <div class="card card-default">
                    <div class="card-header">
                        <span class="card-title">Crear Naturaleza</span>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('naturalezas.store') }}"  role="form" enctype="multipart/form-data">
                            @csrf

                            @include('naturaleza.form')

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
@stop
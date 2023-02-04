@extends('adminlte::page')

@section('title', 'Tramites')

@section('content_header')
    <h1>Naturalezas de Procesos</h1>
@stop

@section('content')
    <section class="content container-fluid">
        <div class="">
            <div class="col-md-12">

                @includeif('partials.errors')

                <div class="card card-default">
                    <div class="card-header">
                        <span class="card-title">Actualizar Naturaleza</span>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('naturalezas.update', $naturaleza->id) }}"  role="form" enctype="multipart/form-data">
                            {{ method_field('PATCH') }}
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
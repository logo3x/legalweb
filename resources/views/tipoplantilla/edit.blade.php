@extends('adminlte::page')

@section('title', 'Tipos Plantillas')


@section('content_header')
    <h1>Tipos Plantillas</h1>
@stop

@section('content')
    <section class="content container-fluid">
        <div class="">
            <div class="col-md-12">

                @includeif('partials.errors')

                <div class="card card-default">
                    <div class="card-header">
                        <span class="card-title">Actualizar Datos</span>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('tipoplantillas.update', $tipoplantilla->id) }}"  role="form" enctype="multipart/form-data">
                            {{ method_field('PATCH') }}
                            @csrf

                            @include('tipoplantilla.form')

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
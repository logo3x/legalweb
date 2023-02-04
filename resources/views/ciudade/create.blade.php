@extends('adminlte::page')

@section('title', 'Ciudades')

@section('content_header')
    <h1>Ciudades</h1>
@stop

@section('content')
    <section class="content container-fluid">
        <div class="row">
            <div class="col-md-12">

                @includeif('partials.errors')

                <div class="card card-default">
                    <div class="card-header">
                        <div class="container">
                            <div class="row justify-content-md-center">
                              <div class="col col-lg-2"><br><br><br>
                                <h4>Nuevo Ciudad</h4>
                              </div>
                              <div class="col-md-auto">
                                <lottie-player src="https://assets9.lottiefiles.com/packages/lf20_ZWN3RI.json"  background="transparent"  speed="1"  style="width: 150px; height: 150px;"  loop  autoplay></lottie-player>
                              </div>
                              
                            </div>
                          </div>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('ciudades.store') }}"  role="form" enctype="multipart/form-data">
                            @csrf

                            @include('ciudade.form')

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
<script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script> 
@stop
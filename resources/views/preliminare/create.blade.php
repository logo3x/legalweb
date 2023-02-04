@extends('adminlte::page')

@section('title', 'Preliminares')
@section('plugins.Select2', true)

@section('content_header')
    <h1>Preliminares</h1>
@stop

@section('content')
    <section class="content container-fluid">
        <div class="row">
            <div class="col-md-12">

                @includeif('partials.errors')

                <div class="card card-default">
                    <div class="card-header">
                        <div class="row justify-content-md-center">
                            <div class="col col-lg-2"><br><br><br>
                              <h4>Nuevo contacto Preliminar</h4>
                            </div>
                            <div class="col-md-auto">
                              <lottie-player src="https://assets9.lottiefiles.com/packages/lf20_q1g5qcgm.json"  background="transparent"  speed="1"  style="width: 150px; height: 150px;"  loop  autoplay></lottie-player>
                            </div>
                            
                          </div>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('preliminares.store') }}"  role="form" enctype="multipart/form-data">
                            @csrf

                            @include('preliminare.form')

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
<script>
    $(document).ready(function() {
    $('#clientes').select2();
});
</script> 
<script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script> 



<script src="https://cdn.tiny.cloud/1/q551vshk1ayt3jkz4o31llz4br85l9aqed7lr4lc01zrbak1/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
   tinymce.init({
     selector: 'textarea#myeditorinstance', // Replace this CSS selector to match the placeholder element for TinyMCE
     plugins: 'powerpaste advcode table lists checklist',
     toolbar: 'undo redo | blocks| bold italic | bullist numlist checklist | code | table'
   });
</script>



@stop
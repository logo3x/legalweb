@extends('adminlte::page')

@section('title', 'Procesos')
@section('plugins.Select2', true)

@section('content_header')
    <h1>Procesos</h1>
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
                                <h4>Nuevo Proceso</h4>
                              </div>
                              <div class="col-md-auto">
                                <lottie-player src="https://assets5.lottiefiles.com/packages/lf20_nzoh1ccm.json"  background="transparent"  speed="1"  style="width: 150px; height: 150px;"  loop  autoplay></lottie-player>
                              </div>
                              
                            </div>
                          </div>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('procesos.store') }}"  role="form" enctype="multipart/form-data">
                            @csrf

                            @include('proceso.form')

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

{{-- Select dependiente de cliente -> preliminar --}}

<script>
    $('#clientes').on('change', function(e){
        console.log(e);
        var categoria = e.target.value;
        
        $.get('selectpreliminar/' + categoria,function(data) {
        
        $('#preliminares').empty();
        
        $.each(data, function(fetch, lista){
            console.log(data);
            for(i = 0; i < lista.length; i++){
            $('#preliminares').append('<option value="'+ lista[i].id +'">'+ lista[i].des_gestion +'</option>');
            }
        })
     })
    });
</script>


<script>
    $(document).ready(function() {
    $('#ciudades').select2();
});

    $(document).ready(function() {
    $('#tipoproceso').select2();
});

$(document).ready(function() {
    $('#preliminares').select2();
});
$(document).ready(function() {
    $('#clientes').select2();
});
$(document).ready(function() {
    $('#claseproceso').select2();
});
$(document).ready(function() {
    $('#naturaleza').select2();
});
$(document).ready(function() {
    $('#juzgado').select2();
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
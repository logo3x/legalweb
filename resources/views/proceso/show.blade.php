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
                <div class="card">
                    <div class="card-header">
                        <div class="float-left">
                            <span class="card-title">Ver el Proceso</span>
                        </div>
                        <div class="float-right">
                            <a class="btn btn-primary" href="{{ route('procesos.index') }}"> Volver</a>
                        </div>
                    </div>

                    <div class="card-body">




                        <div class="container text-center">
                            <div class="row shadow p-3 mb-5 bg-body rounded">
                              <div class="col">
                                <div class="form-group">
                                    <strong>Numero de Proceso:</strong><br>
                                    {{ $proceso->nproceso }}
                                </div>
                              </div>
                              <div class="col">
                                <div class="form-group">
                                    <strong>Nombre:</strong><br>
                                    {{ $proceso->nombre }}
                                </div>
                              </div>
                              <div class="col">
                                <button class="btn btn-success">Ver Preliminar</button>
                              </div>
                            </div>
                            <div class="row shadow p-3 mb-5 bg-body rounded">
                                <div class="col">
                                    <div class="form-group">
                                        <strong>Descripcion:</strong><br>
                                        {{ $proceso->descripcion }}
                                    </div>
                                </div>
                            </div>
                            <div class="row shadow p-3 mb-5 bg-body rounded">
                                <div class="col">                                    
                                    <div class="form-group">
                                        <strong>Cliente:</strong><br>
                                        {{ $proceso->cliente->nombre }}
                                    </div>                                    
                                </div>
                                <div class="col">
                                    <div class="form-group">
                                        <strong>Ciudad:</strong><br>
                                        {{ $proceso->ciudade->nombre }}
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="form-group">
                                        <strong>Juzgado:</strong><br>
                                        {{ $proceso->juzgado->nombre }}
                                    </div>
                                </div>
                            </div>
                            <div class="row shadow p-3 mb-5 bg-body rounded">
                                <div class="col">
                                    <div class="form-group">
                                        <strong>Fecha Presentacion:</strong><br>
                                        {{ $proceso->fecha_presentacion }}
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="form-group">
                                        <strong>Fecha Radicacion:</strong><br>
                                        {{ $proceso->fecha_radicacion }}
                                    </div>
                                </div>
                            </div>
                            <div class="row shadow p-3 mb-5 bg-body rounded">
                                <div class="col">
                                    <div class="form-group">
                                        <strong>Tipo de Procesos:</strong><br>
                                        {{ $proceso->tipoproceso->nombre }}
                                    </div>
                                </div>
                                <div class="col">                                  
                                    <div class="form-group">
                                        <strong>Clase de Proceso:</strong><br>
                                        {{ $proceso->claseproceso->nombre }}
                                    </div>
                                </div>
                                <div class="col">                                  
                                    <div class="form-group">
                                        <strong>Naturaleza:</strong><br>
                                        {{ $proceso->naturaleza->nombre }}
                                    </div>
                                </div>
                            </div>
                            <div class="row shadow p-3 mb-5 bg-body rounded">
                                <div class="col">
                                    <div class="form-group">
                                        <strong>Demandante:</strong><br>
                                        {{ $proceso->demandante }}
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="form-group">
                                        <strong>Contacto Demandante:</strong><br>
                                        {{ $proceso->contacto_demandante }}
                                    </div>
                                </div>
                            </div>
                            <div class="row shadow p-3 mb-5 bg-body rounded">
                                <div class="col">
                                    <div class="form-group">
                                        <strong>Demandado:</strong><br>
                                        {{ $proceso->demandado }}
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="form-group">
                                        <strong>Contacto Demandado:</strong><br>
                                        {{ $proceso->contacto_demandado }}
                                    </div>
                                </div>
                            </div>
                            
                          </div>
                        

                    </div>
                </div>
            </div>
        </div>
       
        

        <div class="accordion accordion-flush" id="accordionFlushExample">
            <div class="accordion-item">
              <h2 class="accordion-header " id="flush-headingOne">
                <button class="accordion-button collapsed " type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseOne" aria-expanded="false" aria-controls="flush-collapseOne">
                  <h5 >Anexos</h5>
                </button>
              </h2>
              <div id="flush-collapseOne" class="accordion-collapse collapse" aria-labelledby="flush-headingOne" data-bs-parent="#accordionFlushExample">
                <div class="accordion-body">
                    <div class="float-right">
                        <a href="{{ route('anexos.create') }}" class="btn btn-primary btn-sm float-right"  data-placement="left">
                        {{ __('Nuevo Anexo') }}
                        </a>
                    </div>  <br>    <br>   
                    <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="thead">
                                    <tr>
										<th>Nombre</th>
										<th>Descripcion</th>
										<th>Anexo</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($anexos as $anexo)
                                        <tr>
											<td>{{ $anexo->nombre }}</td>
											<td>{{ $anexo->descripcion }}</td>
											<td>                                            
                                                @if ($anexo->anexo==false)
                                                     <lottie-player src="https://assets1.lottiefiles.com/packages/lf20_9lxy9vc3.json"  background="transparent"  speed="1"  style="width: 70px; height: 70px;"  loop  autoplay></lottie-player>
                                                 
                                                @else    
                                                    <a href="{{$anexo->getFirstMediaUrl('anexo_anexo')}}" target="_black"><br>VerDescargar<lottie-player src="https://assets8.lottiefiles.com/packages/lf20_szdrhwiq.json"  background="transparent"  speed="1"  style="width: 70px; height: 70px;"  loop  autoplay></lottie-player></a>
                                                     
                                                @endif 
                                            </td>
                                            <td>
                                                <form action="{{ route('anexos.destroy',$anexo->id) }}" method="POST">
                                                    {{-- <a class="btn btn-sm btn-primary " href="{{ route('anexos.show',$anexo->id) }}"><i class="fa fa-fw fa-eye"></i> Ver</a> --}}
                                                    <a class="btn btn-sm btn-success" href="{{ route('anexos.edit',$anexo->id) }}"><i class="fa fa-fw fa-edit"></i> Editar</a>
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm"><i class="fa fa-fw fa-trash"></i> Borrar</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                    </div>
                
                </div>
              </div>
            </div>
            <div class="accordion-item">
              <h2 class="accordion-header" id="flush-headingTwo">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseTwo" aria-expanded="false" aria-controls="flush-collapseTwo">
                  <h5>Alertas</h5>
                </button>
              </h2>
              <div id="flush-collapseTwo" class="accordion-collapse collapse" aria-labelledby="flush-headingTwo" data-bs-parent="#accordionFlushExample">
                <div class="accordion-body">

                    <div class="float-right">
                        <a href="{{ route('alertas.create') }}" class="btn btn-primary btn-sm float-right"  data-placement="left">
                        {{ __('Nuevo alerta') }}
                        </a>
                    </div>
                    <small>Esta semana vencen las filas en amarillo</small>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">                               
                                <thead class="thead">
                                    <tr>                                       
										<th>Nombre</th>
										<th>Descripcion</th>
										<th>Estado</th>
										<th>Creacion</th>
										<th>Vencimiento</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($alertas as $alerta)
                                    @php                                                
                                    $vencimiento=new DateTime($alerta->vencimiento);
                                    $semanaven=$vencimiento->format('W');
                                    $fecha = new DateTime("now");
                                    $semana = $fecha->format('W');                                    
                                    @endphp
                                        <tr @if($semana==$semanaven) class="table-warning"@endif>     
											<td>{{ $alerta->nombre }}</td>
											<td>{{ $alerta->descripcion }}</td>
											<td>{{ $alerta->estado }}</td>
											<td>{{ $alerta->creacion }}</td>
											<td>{{ $alerta->vencimiento }}</td>
                                            <td>
                                                <form action="{{ route('alertas.destroy',$alerta->id) }}" method="POST">
                                                    {{-- <a class="btn btn-sm btn-primary " href="{{ route('alertas.show',$alerta->id) }}"><i class="fa fa-fw fa-eye"></i> Ver</a> --}}
                                                    <a class="btn btn-sm btn-success" href="{{ route('alertas.edit',$alerta->id) }}"><i class="fa fa-fw fa-edit"></i> Editar</a>
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm"><i class="fa fa-fw fa-trash"></i> Borrar</button>
                                                </form>
                                            </td>                                           
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>



                </div>
              </div>
            </div>
            <div class="accordion-item">
              <h2 class="accordion-header" id="flush-headingThree">
                <button class="accordion-button collapsed " type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseThree" aria-expanded="false" aria-controls="flush-collapseThree">
                  <h5>Actuaciones</h5>
                </button>
              </h2>
              <div id="flush-collapseThree" class="accordion-collapse collapse" aria-labelledby="flush-headingThree" data-bs-parent="#accordionFlushExample">
                <div class="accordion-body">
                    
                    <div class="table-responsive"> 
                    <table class="table table-striped table-hover">
                        <thead class="thead">
                            <tr>
                               
                                <th>Nombre</th>
                                <th>Descripcion</th>
                                <th>Fecha</th>
                                <th>Consecutivo</th>
                                <th>Anexo</th>

                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($actuaciones as $actuacione)
                                <tr>
                                    
                                    <td>{{ $actuacione->nombre }}</td>
                                    <td>{{ $actuacione->descripcion }}</td>
                                    <td>{{ $actuacione->fecha }}</td>
                                    <td>{{ $actuacione->consecutivo }}</td>
                                    {{-- <td>{{ $actuacione->anexo }}</td> --}}
                                    <td>                                            
                                        @if ($actuacione->anexo==false)
                                             <lottie-player src="https://assets1.lottiefiles.com/packages/lf20_9lxy9vc3.json"  background="transparent"  speed="1"  style="width: 70px; height: 70px;"  loop  autoplay></lottie-player>
                                         
                                        @else    
                                            <a href="{{$actuacione->getFirstMediaUrl('anexo_actuaciones')}}" target="_black"><br>Ver/Descargar<lottie-player src="https://assets8.lottiefiles.com/packages/lf20_szdrhwiq.json"  background="transparent"  speed="1"  style="width: 70px; height: 70px;"  loop  autoplay></lottie-player></a>
                                             
                                        @endif 
                                    </td>

                                    <td>
                                        <form action="{{ route('actuaciones.destroy',$actuacione->id) }}" method="POST">
                                            {{-- <a class="btn btn-sm btn-primary " href="{{ route('actuaciones.show',$actuacione->id) }}"><i class="fa fa-fw fa-eye"></i> Ver</a> --}}
                                            <a class="btn btn-sm btn-success" href="{{ route('actuaciones.edit',$actuacione->id) }}"><i class="fa fa-fw fa-edit"></i> Editar</a>
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm"><i class="fa fa-fw fa-trash"></i> Borrar</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    </div>
                </div>
              </div>
            </div>
          </div>
   
   
   <br><br><br><br><br>
   
        </section>
@endsection


@section('css')
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
@stop


@section('js')   

<script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
@stop

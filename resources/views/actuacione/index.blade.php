@extends('adminlte::page')

@section('title', 'Actuaciones')

@section('content_header')
    <h1>Actuaciones</h1>
@stop

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header">
                        <div style="display: flex; justify-content: space-between; align-items: center;">

                            <span id="card_title">
                                {{ __('Actuaciones') }}
                            </span>

                             <div class="float-right">
                                <a href="{{ route('actuaciones.create') }}" class="btn btn-primary btn-sm float-right"  data-placement="left">
                                  {{ __('Nueva Actuacion') }}
                                </a>
                              </div>
                        </div>
                    </div>
                    @if ($message = Session::get('success'))
                        <div class="alert alert-success">
                            <p>{{ $message }}</p>
                        </div>
                    @endif

                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="thead">
                                    <tr>
                                        <th>No</th>
                                        
										<th>Proceso</th>
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
                                            <td>{{ ++$i }}</td>
                                            
											<td>{{ $actuacione->proceso->nproceso }}</td>
											<td>{{ $actuacione->nombre }}</td>
											<td>{{ $actuacione->descripcion }}</td>
											<td>{{ $actuacione->fecha }}</td>
											<td>{{ $actuacione->consecutivo }}</td>
											{{-- <td>{{ $actuacione->anexo }}</td> --}}
                                            <td>                                            
                                                @if ($actuacione->anexo==false)
                                                     <lottie-player src="https://assets1.lottiefiles.com/packages/lf20_9lxy9vc3.json"  background="transparent"  speed="1"  style="width: 70px; height: 70px;"  loop  autoplay></lottie-player>
                                                 
                                                @else    
                                                    <a href="{{$actuacione->getFirstMediaUrl('anexo_actuaciones')}}" target="_black"><br>VerDescargar<lottie-player src="https://assets8.lottiefiles.com/packages/lf20_szdrhwiq.json"  background="transparent"  speed="1"  style="width: 70px; height: 70px;"  loop  autoplay></lottie-player></a>
                                                     
                                                @endif 
                                            </td>

                                            <td>
                                                <form action="{{ route('actuaciones.destroy',$actuacione->id) }}" method="POST">
                                                    <a class="btn btn-sm btn-primary " href="{{ route('actuaciones.show',$actuacione->id) }}"><i class="fa fa-fw fa-eye"></i></a>
                                                    <a class="btn btn-sm btn-success" href="{{ route('actuaciones.edit',$actuacione->id) }}"><i class="fa fa-fw fa-edit"></i></a>
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm"><i class="fa fa-fw fa-trash"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                {!! $actuaciones->links() !!}
            </div>
        </div>
    </div>
@endsection

@section('css')
@stop

@section('js')   
<script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>  
@stop

@extends('adminlte::page')

@section('title', 'Alertas')

@section('content_header')
    <h1>Alertas </h1><small>Hoy es @php $psemana=date("d-m-Y", strtotime("+1 week")); echo strftime("%A %d de %B del %Y")."<br/>";echo "En 8 Dias ".strftime("%A %d de %B del %Y" , strtotime("+1 week"));@endphp</small>
@stop

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                                 <h4>
                                <small>
                                 @php                                 
                                 /* echo "<small>Hoy  ".date("d-m-Y")."<br/>";
                                 echo "Mañana  ".date("d-m-Y", strtotime("+1 day"))."<br/>"; 
                                 echo "<small>Hoy en 8 es:  ".date("d-m-Y", strtotime("+1 week"))."<br/>";   */
                                 @endphp
                                 Mañana: 
                                 <ul>
                                 @foreach ($NotificacionDiarias as $NotificacionDiaria)
                                   <li>{{$NotificacionDiaria->nombre}} </li>
                                 @endforeach
                                </ul>
                                Proxima Semana: 
                                <ul>
                                @foreach ($NotificacionSemanals as $NotificacionSemanal)
                                  <li>{{$NotificacionSemanal->nombre}} </li>
                                @endforeach
                               </ul>
                                </small>
                                 </h4>
                                <lottie-player src="https://assets6.lottiefiles.com/packages/lf20_z4cshyhf.json"  background="transparent"  speed="1"  style="width: 150px; height: 150px;"  loop  autoplay></lottie-player>
                                    
                                <div class="float-right">
                                    <a href="{{ route('alertas.create') }}" class="btn btn-primary btn-sm float-right"  data-placement="left">
                                    {{ __('Nuevo alerta') }}
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
                        <small>Si la fila esta en amarillo, el vencimiento es esta semana</small>
                        
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                               
                                <thead class="thead">
                                    <tr>
                                        <th>No</th>
                                        
										<th>Proceso</th>
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
                                            <td>{{ ++$i }}</td>
                                            
											<td>{{ $alerta->proceso->nproceso }}</td>
											<td>{{ $alerta->nombre }}</td>
											<td>{{ $alerta->descripcion }}</td>
											<td>{{ $alerta->estado }}</td>
											<td>{{ $alerta->creacion }}</td>
											<td>{{ $alerta->vencimiento }}</td>

                                            <td>
                                                <form action="{{ route('alertas.destroy',$alerta->id) }}" method="POST">
                                                   {{--  <a class="btn btn-sm btn-primary " href="{{ route('alertas.show',$alerta->id) }}"><i class="fa fa-fw fa-eye"></i> Ver</a> --}}
                                                    <a class="btn btn-sm btn-success" href="{{ route('alertas.edit',$alerta->id) }}"><i class="fa fa-fw fa-edit"></i></a>
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
                {!! $alertas->links() !!}
            </div>
        </div>
    </div>
@endsection

@section('css')
@stop

@section('js')   
<script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>  
@stop
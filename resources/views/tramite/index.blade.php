@extends('adminlte::page')

@section('title', 'Tramites')


@section('content_header')
    <h1>Tramites</h1>
@stop

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header">
                        <div style="display: flex; justify-content: space-between; align-items: center;">

                            <span id="card_title">
                                {{ __('Tramite') }}
                            </span>

                             <div class="float-right">
                                <a href="{{ route('tramites.create') }}" class="btn btn-primary btn-sm float-right"  data-placement="left">
                                  {{ __('Nuevo Tramite') }}
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
                                        
										<th>Tipo de proceso</th>
										<th>Nombre</th>
										<th>Desc Esquema</th>
										<th>Desc Tramite</th>
										<th>Anexo Esquema</th>

                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($tramites as $tramite)
                                        <tr>
                                            <td>{{ ++$i }}</td>
                                            
											<td>{{ $tramite->tipoproceso->nombre }}</td>
											<td>{{ $tramite->nombre }}</td>
											<td>{{ $tramite->desc_esquema }}</td>
											<td>{{ $tramite->desc_tramite }}</td>
											
                                            <td>                                            
                                                @if ($tramite->anexo_esquema==false)
                                                     <lottie-player src="https://assets1.lottiefiles.com/packages/lf20_9lxy9vc3.json"  background="transparent"  speed="1"  style="width: 70px; height: 70px;"  loop  autoplay></lottie-player>
                                                 
                                                @else    
                                                    <a href="{{$tramite->getFirstMediaUrl('anexo_esquema')}}" target="_black"><br>Descargar<lottie-player src="https://assets8.lottiefiles.com/packages/lf20_szdrhwiq.json"  background="transparent"  speed="1"  style="width: 70px; height: 70px;"  loop  autoplay></lottie-player></a>
                                                     
                                                @endif 
                                            </td>

                                            <td>
                                                <form action="{{ route('tramites.destroy',$tramite->id) }}" method="POST">
                                                    <a class="btn btn-sm btn-primary " href="{{ route('tramites.show',$tramite->id) }}"><i class="fa fa-fw fa-eye"></i></a>
                                                    <a class="btn btn-sm btn-success" href="{{ route('tramites.edit',$tramite->id) }}"><i class="fa fa-fw fa-edit"></i></a>
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
                {!! $tramites->links() !!}
            </div>
        </div>
    </div>
@endsection

@section('css')
@stop

@section('js')   
<script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
@stop
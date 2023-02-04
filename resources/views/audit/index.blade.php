@extends('adminlte::page')

@section('title', 'Auditoria')

@section('content_header')
    <h1>Auditoria</h1>
@stop

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header">
                        <div style="display: flex; justify-content: space-between; align-items: center;">

                            <span id="card_title">
                                {{ __('Eventos') }}
                            </span>

                             <div class="float-right">
                                {{-- <a href="{{ route('audit.create') }}" class="btn btn-primary btn-sm float-right"  data-placement="left">
                                  {{ __('Create New') }} --}}
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
                                        
										<th>User Type</th>
										<th>User Id</th>
										<th>Event</th>
										<th>Auditable Type</th>
										<th>Auditable Id</th>
										<th>Old Values</th>
										<th>New Values</th>
										<th>Url</th>
										<th>Ip Address</th>
										<th>User Agent</th>
										<th>Tags</th>

                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($audits as $audit)
                                        <tr>
                                            <td>{{ ++$i }}</td>
                                            
											<td>{{ $audit->user_type }}</td>
											<td>{{ $audit->user_id }}</td>
											<td>{{ $audit->event }}</td>
											<td>{{ $audit->auditable_type }}</td>
											<td>{{ $audit->auditable_id }}</td>
											<td>{{ $audit->old_values }}</td>
											<td>{{ $audit->new_values }}</td>
											<td>{{ $audit->url }}</td>
											<td>{{ $audit->ip_address }}</td>
											<td>{{ $audit->user_agent }}</td>
											<td>{{ $audit->tags }}</td>

                                            <td>
                                                {{-- <a class="btn btn-sm btn-primary " href="{{ route('audit.show',$audit->id) }}"><i class="fa fa-fw fa-eye"></i></a> --}}
                                                {{-- <form action="{{ route('audit.destroy',$audit->id) }}" method="POST">
                                                    
                                                    <a class="btn btn-sm btn-success" href="{{ route('audit.edit',$audit->id) }}"><i class="fa fa-fw fa-edit"></i></a>
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm"><i class="fa fa-fw fa-trash"></i></button>
                                                </form> --}}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                {!! $audits->links() !!}
            </div>
        </div>
    </div>
@endsection


@section('css')
@stop

@section('js')   
<script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>  
@stop
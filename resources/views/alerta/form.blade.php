<div class="box box-info padding-1">
    <div class="box-body">



        <div class="container">
            <div class="row">
              <div class="col">
                <div class="form-group">
                    
                    {{ Form::label('Proceso') }}
                        @if ($alerta->id_proceso==null)
                        <select   class="form-select" name="id_proceso" id="procesos"  style="width:100%" data-placement="left">                       
                            @foreach ($procesos as $proceso)                      
                                <option value="{{ $proceso->id }}" >{{  $proceso->nombre }}</option>     
                            @endforeach      
                        </select>
                        {{-- {{ Form::text('id_proceso', $alerta->id_proceso, ['class' => 'form-control' . ($errors->has('id_proceso') ? ' is-invalid' : ''), 'placeholder' => 'Id Proceso']) }} --}}
                        @else
                        <select   class="form-select" name="id_proceso" id="procesos"  style="width:100%" data-placement="left"> 
                            <option value="{{ $alerta->proceso->id}}" selected>{{$alerta->proceso->nombre}}</option >                   
                            @foreach ($procesos as $proceso)                      
                                <option value="{{ $proceso->id }}" >{{  $proceso->nombre }}</option>     
                            @endforeach      
                        </select>
                        
                        {{-- {{ Form::text('id_proceso', $actuacione->proceso->nombre, ['class' => 'form-control' . ($errors->has('id_proceso') ? ' is-invalid' : ''), 'placeholder' => 'Id Proceso']) }} --}}
                        @endif
                    {{-- {{ Form::text('id_proceso', $alerta->id_proceso, ['class' => 'form-control' . ($errors->has('id_proceso') ? ' is-invalid' : ''), 'placeholder' => 'Id Proceso']) }} --}}
                    {!! $errors->first('id_proceso', '<div class="invalid-feedback">:message</div>') !!}
                </div>
              </div>
              <div class="col">
                <div class="form-group">
                    {{ Form::label('Nombre') }}
                    {{ Form::text('nombre', $alerta->nombre, ['class' => 'form-control' . ($errors->has('nombre') ? ' is-invalid' : ''), 'placeholder' => 'Nombre']) }}
                    {!! $errors->first('nombre', '<div class="invalid-feedback">:message</div>') !!}
                </div>
              </div>
            </div>
            <div class="row">
                <div class="col">
                    <div class="form-group">
                        {{ Form::label('Descripcion') }}
                        {{ Form::text('descripcion', $alerta->descripcion, ['class' => 'form-control' . ($errors->has('descripcion') ? ' is-invalid' : ''), 'placeholder' => 'Descripcion']) }}
                        {!! $errors->first('descripcion', '<div class="invalid-feedback">:message</div>') !!}
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col">
                    <div class="form-group">
                        {{ Form::label('Estado') }}
                        <select name="estado" class="form-select" aria-label="Default select example">      
                                              
                            <option value="Pendiente" {{$alerta->estado=="Pendiente"? 'selected':''}}>Pendiente</option>
                            <option value="Tramitado"{{$alerta->estado=="Tramitado"? 'selected':''}}>Tramitado</option>
                            <option value="Vencido"{{$alerta->estado=="Vencido"? 'selected':''}}>Vencido</option>
                          </select>

                          
                        {{-- {{ Form::text('estado', $alerta->estado, ['class' => 'form-control' . ($errors->has('estado') ? ' is-invalid' : ''), 'placeholder' => 'Estado']) }} --}}
                        {!! $errors->first('estado', '<div class="invalid-feedback">:message</div>') !!}
                    </div>
                </div>
                <div class="col">
                    <div class="form-group">
                        {{ Form::label('Creacion') }}
                        {{ Form::date('creacion', $alerta->creacion, ['class' => 'form-control' . ($errors->has('creacion') ? ' is-invalid' : ''), 'placeholder' => 'Creacion']) }}
                        {!! $errors->first('creacion', '<div class="invalid-feedback">:message</div>') !!}
                    </div>
                </div>
                <div class="col">
                    <div class="form-group">
                        {{ Form::label('Vencimiento') }}
                        {{ Form::date('vencimiento', $alerta->vencimiento, ['class' => 'form-control' . ($errors->has('vencimiento') ? ' is-invalid' : ''), 'placeholder' => 'Vencimiento']) }}
                        {!! $errors->first('vencimiento', '<div class="invalid-feedback">:message</div>') !!}
                    </div>
                </div>
            </div>
          </div>
        
        

    </div>
    <div class="box-footer mt20">
        <button type="submit" class="btn btn-primary">Enviar</button>
    </div>
</div>
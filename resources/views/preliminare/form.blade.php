<div class="box box-info padding-1">
    <div class="box-body">
        
        <div class="container">
            <div class="row">
              <div class="col">
                <div class="form-group">
                    {{ Form::label('Fecha') }}
                    {{ Form::date('fecha', $preliminare->fecha, ['class' => 'form-control' . ($errors->has('fecha') ? ' is-invalid' : ''), 'placeholder' => 'Fecha']) }}
                    {!! $errors->first('fecha', '<div class="invalid-feedback">:message</div>') !!}
                </div>
              </div>
              <div class="col">
                <div class="form-group">
                    {{ Form::label('Cliente') }}

                    @if ($preliminare->id_cliente==null)
                    <select   class="form-select" name="id_cliente" id="clientes"  style="width:100%" data-placement="left">                       
                        @foreach ($clientes as $cliente)                      
                            <option value="{{ $cliente->id }}" >{{  $cliente->nombre }}</option>     
                        @endforeach      
                    </select>
                    {{-- {{ Form::text('id_proceso', $preliminare->id_proceso, ['class' => 'form-control' . ($errors->has('id_proceso') ? ' is-invalid' : ''), 'placeholder' => 'Id Proceso']) }} --}}
                    @else
                    <select   class="form-select" name="id_cliente" id="clientes"  style="width:100%" data-placement="left"> 
                        <option value="{{ $preliminare->cliente->id}}" selected>{{$preliminare->cliente->nombre}}</option >                   
                        @foreach ($clientes as $cliente)                      
                            <option value="{{ $cliente->id }}" >{{  $cliente->nombre }}</option>     
                        @endforeach      
                    </select>
                    
                    {{-- {{ Form::text('id_proceso', $actuacione->proceso->nombre, ['class' => 'form-control' . ($errors->has('id_proceso') ? ' is-invalid' : ''), 'placeholder' => 'Id Proceso']) }} --}}
                    @endif




                    {{-- {{ Form::text('id_cliente', $preliminare->id_cliente, ['class' => 'form-control' . ($errors->has('id_cliente') ? ' is-invalid' : ''), 'placeholder' => 'Id Cliente']) }} --}}
                    {!! $errors->first('id_cliente', '<div class="invalid-feedback">:message</div>') !!}
                </div>
              </div>
            </div>
            <div class="row">
                <div class="col">
                    <div class="form-group">
                        {{ Form::label('Relato de los hechos') }}
                        {{ Form::textarea('relato', $preliminare->relato, ['class' => 'form-control' . ($errors->has('relato') ? ' is-invalid' : ''), 'placeholder' => 'Relato','id' => 'myeditorinstance']) }}
                        {!! $errors->first('relato', '<div class="invalid-feedback">:message</div>') !!}
                    </div>
                </div>
              </div>
              <div class="row">
                <div class="col">
                    <div class="form-group">
                        {{ Form::label('Gestion') }}
                        <select name="gestion" class="form-select" aria-label="Default select example">                                                  
                            <option value="Pendiente" {{$preliminare->gestion=="Pendiente"? 'selected':''}}>Pendiente</option>
                            <option value="Tramitado"{{$preliminare->gestion=="Finalizado"? 'selected':''}}>Finalizado</option>
                          </select>
                        {{-- {{ Form::text('gestion', $preliminare->gestion, ['class' => 'form-control' . ($errors->has('gestion') ? ' is-invalid' : ''), 'placeholder' => 'Gestion']) }} --}}
                        {!! $errors->first('gestion', '<div class="invalid-feedback">:message</div>') !!}
                    </div>
                </div>
                <div class="col">
                    <div class="form-group">
                        {{ Form::label('des_gestion') }}
                        {{ Form::text('des_gestion', $preliminare->des_gestion, ['class' => 'form-control' . ($errors->has('des_gestion') ? ' is-invalid' : ''), 'placeholder' => 'Des Gestion']) }}
                        {!! $errors->first('des_gestion', '<div class="invalid-feedback">:message</div>') !!}
                    </div>
                </div>
              </div>
          </div>



        
        
        
        

    </div>
    <div class="box-footer mt20">
        <button type="submit" class="btn btn-primary">Enviar</button>
    </div>
</div>
<div class="box box-info padding-1">
    <div class="box-body">
        



        <div class="container">
            <div class="row">
              <div class="col">                
                    <div class="form-group">
                        {{ Form::label('Proceso') }}
                        @if ($anexo->id_proceso==null)
                                    <select   class="form-select" name="id_proceso" id="procesos"  style="width:100%" data-placement="left">                       
                                        @foreach ($procesos as $proceso)                      
                                            <option value="{{ $proceso->id }}" >{{  $proceso->nombre }}</option>     
                                        @endforeach      
                                    </select>
                                    {{-- {{ Form::text('id_proceso', $anexo->id_proceso, ['class' => 'form-control' . ($errors->has('id_proceso') ? ' is-invalid' : ''), 'placeholder' => 'Id Proceso']) }} --}}
                                    @else
                                    <select   class="form-select" name="id_proceso" id="procesos"  style="width:100%" data-placement="left"> 
                                        <option value="{{ $anexo->proceso->id}}" selected>{{$anexo->proceso->nombre}}</option >                   
                                        @foreach ($procesos as $proceso)                      
                                            <option value="{{ $proceso->id }}" >{{  $proceso->nombre }}</option>     
                                        @endforeach      
                                    </select>
                                    
                                    {{-- {{ Form::text('id_proceso', $actuacione->proceso->nombre, ['class' => 'form-control' . ($errors->has('id_proceso') ? ' is-invalid' : ''), 'placeholder' => 'Id Proceso']) }} --}}
                                    @endif
                        {{-- {{ Form::text('id_proceso', $anexo->id_proceso, ['class' => 'form-control' . ($errors->has('id_proceso') ? ' is-invalid' : ''), 'placeholder' => 'Id Proceso']) }} --}}
                        {!! $errors->first('id_proceso', '<div class="invalid-feedback">:message</div>') !!}
                    </div>
              </div>
              <div class="col">
                <div class="form-group">
                    {{ Form::label('Nombre') }}
                    {{ Form::text('nombre', $anexo->nombre, ['class' => 'form-control' . ($errors->has('nombre') ? ' is-invalid' : ''), 'placeholder' => 'Nombre']) }}
                    {!! $errors->first('nombre', '<div class="invalid-feedback">:message</div>') !!}
                </div>
              </div>
            </div>
            <div class="row">
                <div class="col">
                    <div class="form-group">
                        {{ Form::label('Descripcion') }}
                        {{ Form::text('descripcion', $anexo->descripcion, ['class' => 'form-control' . ($errors->has('descripcion') ? ' is-invalid' : ''), 'placeholder' => 'Descripcion']) }}
                        {!! $errors->first('descripcion', '<div class="invalid-feedback">:message</div>') !!}
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col">
                    <div class="form-group">
                        

                        @if ($anexo->anexo==false)
                        <div class="row">
                            <div class="col-12">
                                    <div class="mb-3">
                                        <label for="formFile" class="form-label">Anexo</label>
                                        <input name="anexo" class="form-control" type="file" id="formFile">
                                    </div>
                            </div>
                        </div>
                        @else 
                        <div class="row">
                                <div class="col-2">             
                                    {{ Form::label('Anexo') }}
                                    <a href="{{$anexo->getFirstMediaUrl('anexo_anexo')}}" target="_black"><br>Descargar<lottie-player src="https://assets6.lottiefiles.com/packages/lf20_jaxz4fko.json"  background="transparent"  speed="1"  style="width: 150px; height: 150px;"  loop  autoplay></lottie-player></a>
                                </div>
                                <div class="col-10">
                                        <div class="mb-3">
                                            <label for="formFile" class="form-label">Actualizar Anexo</label>
                                            <input name="anexo" class="form-control" type="file" id="formFile">
                                        </div>
                                </div>
                            </div>
                            
                        @endif


                        {{-- {{ Form::text('anexo', $anexo->anexo, ['class' => 'form-control' . ($errors->has('anexo') ? ' is-invalid' : ''), 'placeholder' => 'Anexo']) }} --}}
                        {!! $errors->first('anexo', '<div class="invalid-feedback">:message</div>') !!}
                    </div>
                </div>
            </div>
          </div>







        
        
       

    </div>
    <div class="box-footer mt20">
        <button type="submit" class="btn btn-primary">Enviar</button>
    </div>
</div>
<div class="box box-info padding-1">
    <div class="box-body">
        
        

        <div class="container text-center">
            <div class="row">
                <div class="col">
                    {{ Form::label('Tipo de proceso') }}
                   


                    
                    @if ($tramite->id_tipoproceso==null)
                    <select   class="form-select" name="id_tipoproceso" id="tipotramite"  style="width:100%" data-placement="left">                       
                        @foreach ($tipoprocesos as $tipos)                      
                            <option value="{{ $tipos->id }}" >{{  $tipos->nombre }}</option>     
                        @endforeach      
                    </select>
                    {{-- {{ Form::text('id_proceso', $tramite->id_proceso, ['class' => 'form-control' . ($errors->has('id_proceso') ? ' is-invalid' : ''), 'placeholder' => 'Id Proceso']) }} --}}
                    @else
                    <select   class="form-select" name="id_tipoproceso" id="tipotramite"  style="width:100%" data-placement="left"> 
                        <option value="{{ $tramite->tipoproceso->id}}" selected>{{$tramite->tipoproceso->nombre}}</option >                   
                        @foreach ($tipoprocesos as $tipos)                      
                            <option value="{{ $tipos->id }}" >{{  $tipos->nombre }}</option>     
                        @endforeach      
                    </select>
                    
                    {{-- {{ Form::text('id_proceso', $actuacione->proceso->nombre, ['class' => 'form-control' . ($errors->has('id_proceso') ? ' is-invalid' : ''), 'placeholder' => 'Id Proceso']) }} --}}
                    @endif

                    
                    {!! $errors->first('id_tipoproceso', '<div class="invalid-feedback">:message</div>') !!}




                </div>
                <div class="col">
                    <div class="form-group">
                        {{ Form::label('Nombre') }}
                        {{ Form::text('nombre', $tramite->nombre, ['class' => 'form-control' . ($errors->has('nombre') ? ' is-invalid' : ''), 'placeholder' => 'Nombre']) }}
                        {!! $errors->first('nombre', '<div class="invalid-feedback">:message</div>') !!}
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col">
                    <div class="form-group">
                        {{ Form::label('Descripcion del Esquema') }}
                        {{ Form::text('desc_esquema', $tramite->desc_esquema, ['class' => 'form-control' . ($errors->has('desc_esquema') ? ' is-invalid' : ''), 'placeholder' => 'Desc Esquema']) }}
                        {!! $errors->first('desc_esquema', '<div class="invalid-feedback">:message</div>') !!}
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col">
                    <div class="form-group">
                        {{ Form::label('Descripcion del Tramite') }}
                        {{ Form::text('desc_tramite', $tramite->desc_tramite, ['class' => 'form-control' . ($errors->has('desc_tramite') ? ' is-invalid' : ''), 'placeholder' => 'Desc Tramite']) }}
                        {!! $errors->first('desc_tramite', '<div class="invalid-feedback">:message</div>') !!}
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col">


                    <div class="form-group">         
                        @if ($tramite->anexo_esquema==false)
                        <div class="row">
                            <div class="col-12">
                                    <div class="mb-3">
                                        <label for="formFile" class="form-label">Esquema</label>
                                        <input name="anexo_esquema" class="form-control" type="file" id="formFile">
                                    </div>
                            </div>
                        </div>
                          @else 
                          <div class="row">
                                <div class="col-2">             
                                    {{ Form::label('tramite') }}
                                    <a href="{{$tramite->getFirstMediaUrl('anexo_esquema')}}" target="_black"><br>Descargar<lottie-player src="https://assets8.lottiefiles.com/packages/lf20_szdrhwiq.json"  background="transparent"  speed="1"  style="width: 100px; height: 100px;"  loop  autoplay></lottie-player></a>
                                </div>
                                <div class="col-10">
                                        <div class="mb-3">
                                            <label for="formFile" class="form-label">Actualizar Esquema</label>
                                            <input name="anexo_esquema" class="form-control" type="file" id="formFile">
                                        </div>
                                </div>
                             </div>
                            
                        @endif
                        {{-- {{ Form::file('anexo', $tramite->anexo, ['class' => 'form-control form-control-lg' . ($errors->has('anexo') ? ' is-invalid' : ''), 'placeholder' => 'Anexo']) }} --}}
                        {!! $errors->first('anexo_esquema', '<div class="invalid-feedback">:message</div>') !!}
                    </div>






                   {{--  <div class="form-group">
                        {{ Form::label('Esquema') }}
                        {{ Form::text('anexo_esquema', $tramite->anexo_esquema, ['class' => 'form-control' . ($errors->has('anexo_esquema') ? ' is-invalid' : ''), 'placeholder' => 'Anexo Esquema']) }}
                        {!! $errors->first('anexo_esquema', '<div class="invalid-feedback">:message</div>') !!}
                    </div> --}}
                </div>
            </div>
        </div>















                   
        
        
       
        
        

    </div>
    <div class="box-footer mt20">
        <button type="submit" class="btn btn-primary">Enviar</button>
    </div>
</div>
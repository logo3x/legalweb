@php( $login_url = View::getSection('login_url') ?? config('adminlte.login_url', 'login') )
@php( $password_reset_url = View::getSection('password_reset_url') ?? config('adminlte.password_reset_url', 'password/reset') )

<style>
  a {
    text-decoration: none;
  }
  .login-page {
    width: 100%;
    height: 100vh;
    display: inline-block;
    display: flex;
    align-items: center;
  }
  .form-right i {
    font-size: 100px;
  }
  </style>




  <div class="login-page bg-light">
    <div class="container">
        <div class="row">
            <div class="col-lg-10 offset-lg-1">
              <h3 class="mb-3">Iniciar Ahora</h3>
                <div class="bg-white shadow rounded">
                    <div class="row">
                        <div class="col-md-7 pe-0">
                            <div class="form-left text-center h-100 py-5 px-5">
                              <br><br><br><br>
                              <form action="{{ $login_url }}" method="post">
                                @csrf
                                    
                                          {{-- Email field --}}
                                    <div class="input-group mb-3">
                                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                              value="{{ old('email') }}" placeholder="{{ __('adminlte::adminlte.email') }}" autofocus>
                            
                                        <div class="input-group-append">
                                            <div class="input-group-text">
                                                <span class="fas fa-envelope {{ config('adminlte.classes_auth_icon', '') }}"></span>
                                            </div>
                                        </div>
                            
                                        @error('email')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                              
                                        {{-- Password field --}}
                                    <div class="input-group mb-3">
                                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                                              placeholder="{{ __('adminlte::adminlte.password') }}">
                            
                                        <div class="input-group-append">
                                            <div class="input-group-text">
                                                <span class="fas fa-lock {{ config('adminlte.classes_auth_icon', '') }}"></span>
                                            </div>
                                        </div>
                            
                                        @error('password')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>

                                        <div class="col-12">
                                            <button type="submit" class="btn btn-primary px-4 float-end mt-4">Entrar</button>
                                        </div>
                                </form>
                            </div>
                        </div>
                        <div class="col-md-5 ps-0 d-none d-md-block">
                            
                                
                                <img src="vendor/adminlte/dist/img/banner-login.png" alt="LEGAL WEB" class="w-100 rounded-t-5 rounded-tr-lg-0 rounded-bl-lg-5" />
                            
                        </div>
                    </div>
                </div>
                <p class="text-end text-secondary mt-3">Desarrollado por:<a href="http://holdingti.com/" target="_blank" rel="noopener noreferrer">Holding TI</a> </p>
            </div>
        </div>
    </div>
</div>

  





@extends('adminlte::auth.auth-page', ['auth_type' => 'login'])




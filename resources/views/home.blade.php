@extends('layouts.panel')

@section('content')
<section class="section">
   
    <div class="section-body">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">                          
                            <div class="row">
                                <div class="col-md-4 col-xl-4">
                                
                                <div class="card bg-c-blue order-card">
                                        <div class="card-block">
                                        <h5>Usuarios</h5>                                               
                                            @php
                                             use App\Models\User;
                                            $cant_usuarios = User::count();                                                
                                            @endphp
                                            <h2 class="text-right"><i class="fa fa-users f-left"></i><span>{{$cant_usuarios}}</span></h2>
                                            <p class="m-b-0 text-right"><a href="/usuarios" class="text-white">Ver más</a></p>
                                        </div>                                            
                                    </div>                                    
                                </div>
                                
                                <div class="col-md-4 col-xl-4">
                                    <div class="card bg-c-green order-card">
                                        <div class="card-block">
                                        <h5>Roles</h5>                                               
                                            @php
                                            use Spatie\Permission\Models\Role;
                                             $cant_roles = Role::count();                                                
                                            @endphp
                                            <h2 class="text-right"><i class="fa fa-user-lock f-left"></i><span>{{$cant_roles}}</span></h2>
                                            <p class="m-b-0 text-right"><a href="/roles" class="text-white">Ver más</a></p>
                                        </div>
                                    </div>
                                </div>                                                                
                                
                                <div class="col-md-4 col-xl-4">
                                    <div class="card bg-c-pink order-card">
                                        <div class="card-block">
                                            <h5>Blogs</h5>                                               
                                            @php
                                            /*  use App\Models\Blog;
                                            $cant_blogs = Blog::count();  */                                               
                                            @endphp
                                            <h2 class="text-right"><i class="fa fa-blog f-left"></i><span>{{}}</span></h2>
                                            <p class="m-b-0 text-right"><a href="/blogs" class="text-white">Ver más</a></p>
                                        </div>
                                    </div>
                                </div>
                            </div>                        
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div>
                    <div class="info-box">
                        <span class="info-box-icon bg-info"><i class="far fa-envelope"></i></span>
                        <div class="info-box-content">
                        <span class="info-box-text">Messages</span>
                        <span class="info-box-number">1,410</span>
                        </div>
                    </div>
                    <div class="info-box bg-success">
                        <span class="info-box-icon"><i class="far fa-flag"></i></span>
                        <div class="info-box-content">
                        <span class="info-box-text">Bookmarks</span>
                        <span class="info-box-number">410</span>
                        </div>
                    </div>
                    <div class="info-box bg-gradient-warning">
                        <span class="info-box-icon"><i class="far fa-copy"></i></span>
                        <div class="info-box-content">
                        <span class="info-box-text">Uploads</span>
                        <span class="info-box-number">13,648</span>
                        </div>
                    </div>
                </div>                

      <div class="info-box">
        <span class="info-box-icon bg-info"><i class="far fa-bookmark"></i></span>
        <div class="info-box-content">
          <span class="info-box-text">Bookmarks</span>
          <span class="info-box-number">41,410</span>
          <div class="progress">
            <div class="progress-bar bg-info" style="width: 70%"></div>
          </div>
          <span class="progress-description">
            70% Increase in 30 Days
          </span>
        </div>
      </div>
      <div class="small-box bg-info">
        <div class="inner">
          <h3>150</h3>
          <p>New Orders</p>
        </div>
        <div class="icon">
          <i class="fas fa-shopping-cart"></i>
        </div>
        <a href="#" class="small-box-footer">
          More info <i class="fas fa-arrow-circle-right"></i>
        </a>
      </div>
      <div class="overlay dark">
        <i class="fas fa-2x fa-sync-alt fa-spin"></i>
      </div>
</section>
@endsection

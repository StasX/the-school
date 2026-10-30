<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <title>Login</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="/favicon.ico" type="image/x-icon" />

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-body-tertiary">
        <div class="container-fluid">
            <a class="navbar-brand" href="#!school">
                <img alt="Logo" src="img/logo.png" class = "d-inline-block align-text-top" width = "60" />
            </a>
        </div>
    </nav>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12 col-sm-4 offset-sm-4" id="container">
                <form id="login">
                    <div class="row">
                        <img id="login-img" alt="login" src="img/user.png" class="rounded-circle" />
                    </div>
                    <div class="row">
                        <div class="mb-3">
                            <label class="form-label" for="user">Username</label>
                            <input type="text" id="user" name="user" class="form-control" placeholder="Username"
                                spellcheck="false" />
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="password">Password</label>
                            <input type="password" id="password" name="password" class="form-control" placeholder="Password" />
                        </div>
                    </div>
                    <div class="row">
                        <div id="alerts" class="col-10 offset-1 col-md-8 offset-md-2">
                        </div>
                    </div>
                    <div class="row">
                        <button type="submit" class="btn btn-secondary">Login</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>

</html>

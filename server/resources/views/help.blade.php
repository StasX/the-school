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
    <h1>API Documentation</h1>


    <h2>Web Routes</h2>

    <table class="table table-striped">
        <thead>
            <tr>
                <th>Description</th>
                <th>Method + Path</th>
                <th>Request / Response</th>
            </tr>
        </thead>
        <tbody>

            {{-- Auth --}}

            <tr>
                <td>Login to the system</td>
                <td><b>POST</b> /api/login</td>
                <td>
                    <div>
                        <b>Request:</b>
                        <pre>
                            <code>
                                {
                                    "email": string,
                                    "password": string
                                }
                            </code>
                        </pre>
                    </div>
                    <div>
                        <b>Response:</b>
                        <pre>
                            <code>
                                {
                                    "token": csrf_token,
                                    "administrator": {
                                        "id": integer,
                                        "email": string,
                                        "name": string,
                                        "role": string,
                                        "phone": string,
                                        "image": string
                                    }
                                }
                            </code>
                        </pre>
                    </div>
                </td>
            </tr>
            <tr>
                <td>Logout from the system</td>
                <td><b>POST</b> /api/logout</td>
                <td>
                    <div>
                        <b>Request:</b>
                        <pre>
                            <code>
                                (empty)
                            </code>
                        </pre>

                    </div>
                    <div>
                        <b>Response:</b>
                        <pre>
                            <code>
                                {
                                    "message": "Logout successful"
                                }
                            </code>
                        </pre>
                    </div>
                </td>
            </tr>
            <tr>
                <td>Get authentication data</td>
                <td><b>GET</b>/api/auth</td>
                <td>
                    <div>
                        <b>Request:</b>
                        <pre>
                            <code>
                                (empty)
                            </code>
                        </pre>
                    </div>
                    <div>
                        <b>Response:</b>
                        <pre>
                            <code>
                                {
                                    "id": integer,
                                    "email": string,
                                    "name": string,
                                    "role": string,
                                    "phone": string,
                                    "image": string
                                }
                            </code>
                        </pre>
                    </div>
                </td>
            </tr>

            {{-- Administrator --}}

            <tr>
                <td>Get all administrators</td>
                <td><b>GET</b> /api/administrator</td>
                <td>
                    <div>
                        <b>Request:</b>
                        <pre>
                            <code>
                                Query: page=1 (positive integer; optional)
                            </code>
                        </pre>

                    </div>
                    <div>
                        <b>Response:</b>
                        <pre>
                            <code>
                                {
                                    "data": [
                                        {
                                                "id": integer,
                                                "email": string,
                                                "name": string,
                                                "role": string,
                                                "phone": string,
                                                "image": string
                                        }
                                    ],
                                    "links": { "first": string, "last": string, "prev": string|null, "next": string|null },
                                    "meta": { "current_page": integer, "last_page": integer, "per_page": 20, "total": integer, ... }
                                }
                            </code>
                        </pre>
                    </div>
                </td>
            </tr>
            <tr>
                <td>Get administrator by ID</td>
                <td><b>GET</b> /api/administrator/{id}: integer</td>
                <td>
                    <div>
                        <b>Request:</b>
                        <pre>
                            <code>
                                (empty)
                            </code>
                        </pre>
                    </div>
                    <div>
                        <b>Response:</b>
                        <pre>
                            <code>
                                {
                                    "id": integer,
                                    "email": string,
                                    "name": string,
                                    "role": string,
                                    "phone": string,
                                    "image": string
                                }
                            </code>
                        </pre>
                    </div>
                </td>
            </tr>
            <tr>
                <td>Get the total number of administrators</td>
                <td><b>GET</b> /api/administrator/count</td>
                <td>
                    <div>
                        <b>Request:</b>
                        <pre>
                            <code>
                                (empty)
                            </code>
                        </pre>
                    </div>
                    <div>
                        <b>Response:</b>
                        <pre>
                            <code>
                                {
                                    "count": integer
                                }
                            </code>
                        </pre>
                    </div>
                </td>
            </tr>
            <tr>
                <td>Create administrator</td>
                <td><b>POST</b> /api/administrator</td>
                <td>
                    <div>
                        <b>Request:</b>
                        <pre>
                            <code>
                                FormData(
                                    "email": string,
                                    "name": string,
                                    "role": string,
                                    "phone": string,
                                    "password": string,
                                    "image": Blob
                                )
                            </code>
                        </pre>
                    </div>
                    <div>
                        <b>Response:</b>
                        <pre>
                            <code>
                                {
                                    "id": integer,
                                    "email": string,
                                    "name": string,
                                    "role": string,
                                    "phone": string,
                                    "image": string
                                }
                            </code>
                        </pre>
                    </div>
                </td>
            </tr>
            <tr>
                <td>Update administrator</td>
                <td><b>PUT</b> /api/administrator/{id}: integer</td>
                <td>
                    <div>
                        <b>Request:</b>
                        <pre>
                            <code>FormData(
                                    "email": string,
                                    "name": string,
                                    "role": string | null,
                                    "phone": string,
                                    "password": string | null,
                                    "image": Blob | null
                                )
                            </code>
                        </pre>
                    </div>
                    <div>
                        <b>Response:</b>
                        <pre>
                            <code>
                                {
                                    "id": integer,
                                    "email": string,
                                    "name": string,
                                    "role": string,
                                    "phone": string,
                                    "image": string
                                }
                            </code>
                        </pre>
                    </div>
                </td>
            </tr>
            <tr>
                <td>Delete administrator</td>
                <td><b>DELETE</b> /api/administrator/{id}: integer</td>
                <td>
                    <div>
                        <b>Request:</b>
                        <pre>
                            <code>
                                (empty)
                            </code>
                        </pre>

                    </div>
                    <div>
                        <b>Response:</b>
                        <pre>
                            <code>
                                (empty)
                            </code>
                        </pre>
                    </div>
                </td>
            </tr>

            {{-- Student --}}

            <tr>
                <td>Get all students</td>
                <td> <b>GET</b> /api/student</td>
                <td>
                    <div>
                        <b>Request:</b>
                        <pre>
                            <code>
                                Query: page=1 (positive integer; optional)
                            </code>
                        </pre>
                    </div>
                    <div>
                        <b>Response:</b>
                        <pre>
                            <code>
                                {
                                    "data": [
                                        {
                                                "id": integer,
                                                "email": string,
                                                "name": string,
                                                "phone": string,
                                                "image": string
                                        }
                                    ],
                                    "links": { "first": string, "last": string, "prev": string|null, "next": string|null },
                                    "meta": { "current_page": integer, "last_page": integer, "per_page": 20, "total": integer, ... }
                                }
                            </code>
                        </pre>
                    </div>
                </td>
            </tr>
            <tr>
                <td>Get student by ID</td>
                <td><b>GET</b> /api/student/{id}: integer</td>
                <td>
                    <div>
                        <b>Request:</b> (empty)
                    </div>
                    <div>
                        <b>Response:</b>
                        <pre>
                            <code>
                                {
                                    "id": integer,
                                    "email": string,
                                    "name": string,
                                    "phone": string,
                                    "image": string,
                                    "courses": [
                                        {
                                            "id": integer,
                                            "name": string,
                                            "description": string,
                                            "image": string
                                        },
                                        ...
                                    ]
                                }
                            </code>
                        </pre>
                    </div>
                </td>
            </tr>
            <tr>
                <td>Create student</td>
                <td><b>POST</b> /api/student</td>
                <td>
                    <div>
                        <b>Request:</b>
                        <pre>
                            <code>
                                FormData(
                                    "email": string,
                                    "name": string,
                                    "phone": string,
                                    "image": Blob,
                                    "courses": [
                                        {
                                            "id": integer
                                        },
                                        ...
                                    ]
                                )
                            </code>
                        </pre>
                    </div>
                    <div>
                        <b>Response:</b>
                        <pre>
                            <code>
                                {
                                    "id": integer,
                                    "email": string,
                                    "name": string,
                                    "phone": string,
                                    "image": string,
                                    "courses": [
                                        {
                                            "id": integer,
                                            "name": string,
                                            "description": string,
                                            "image": string
                                        },
                                        ...
                                    ]
                                }
                            </code>
                        </pre>
                    </div>
                </td>
            </tr>
            <tr>
                <td>Update student</td>
                <td><b>PUT</b> /api/student</td>
                <td>
                    <div>
                        <b>Request:</b>
                        <pre>
                            <code>
                                FormData(
                                    "email": string,
                                    "name": string,
                                    "phone": string,
                                    "image": Blob |null,
                                    "courses": [
                                        {
                                           "id": integer
                                        },
                                        ...
                                    ]
                                )
                            </code>
                        </pre>
                    </div>
                    <div>
                        <b>Response:</b>
                        <pre>
                            <code>
                                {
                                    "id": integer,
                                    "email": string,
                                    "name": string,
                                    "phone": string,
                                    "image": string,
                                    "courses": [
                                        {
                                            "id": integer,
                                            "name": string,
                                            "description": string,
                                            "image": string
                                        },
                                        ...
                                    ]
                                }
                            </code>
                        </pre>
                    </div>
                </td>
            </tr>
            <tr>
                <td>Delete student</td>
                <td><b>DELETE</b> /api/student/{id}: integer</td>
                <td>
                    <div>
                        <b>Request:</b>
                        <pre>
                            <code>
                                (empty)
                            </code>
                        </pre>
                    </div>
                    <div>
                        <b>Response:</b>
                        <pre>
                            <code>
                                (empty)
                            </code>
                        </pre>
                    </div>
                </td>
            </tr>

            {{-- Course --}}

            <tr>
                <td>Get all courses</td>
                <td><b>GET</b> /api/course</td>
                <td>
                    <div>
                        <b>Request:</b>
                        <pre>
                            <code>
                                Query: page=1 (positive integer; optional)
                            </code>
                        </pre>
                    </div>
                    <div>
                        <b>Response:</b>
                        <pre>
                            <code>
                                {
                                    "data": [
                                        {
                                                "id": integer,
                                                "name": string,
                                                "description": string,
                                                "image": string
                                        }
                                    ],
                                    "links": { "first": string, "last": string, "prev": string|null, "next": string|null },
                                    "meta": { "current_page": integer, "last_page": integer, "per_page": 20, "total": integer, ... }
                                }
                            </code>
                        </pre>
                    </div>
                </td>
            </tr>
            <tr>
                <td>Get course by ID</td>
                <td><b>GET</b> /api/course/{id}: integer</td>
                <td>
                    <div>
                        <b>Request:</b>
                        <pre>
                            <code>
                                (empty)
                            </code>
                        </pre>
                    </div>
                    <div>
                        <b>Response:</b>
                        <pre>
                            <code>
                                {
                                    "id": integer,
                                    "name": string,
                                    "description": string,
                                    "image": string,
                                    "students": [
                                        {
                                            "id": integer,
                                            "email": string,
                                            "name": string,
                                            "phone": string,
                                            "image": string
                                        },
                                        ...
                                    ]
                                }
                            </code>
                        </pre>
                    </div>
                </td>
            </tr>
            <tr>
                <td>Create course</td>
                <td><b>POST</b> /api/course</td>
                <td>
                    <div>
                        <b>Request:</b>
                        <pre>
                            <code>
                                FormData(
                                    "name", string,
                                    "description": string,
                                    "image": Blob
                                )
                            </code>
                        </pre>
                    </div>
                    <div>
                        <b>Response:</b>
                        <pre>
                            <code>
                                {
                                    "id": integer,
                                    "name": string,
                                    "description": string,
                                    "image": string
                                }
                            </code>
                        </pre>
                    </div>
                </td>
            </tr>
            <tr>
                <td>Update course</td>
                <td><b>PUT</b> /api/course/{id}: integer</td>
                <td>
                    <div>
                        <b>Request:</b>
                        <pre>
                            <code>
                                FormData(
                                    "name": string,
                                    "description": string,
                                    "image": Blob | null
                                )
                            </code>
                        </pre>
                    </div>
                    <div>
                        <b>Response:</b>
                        <pre>
                            <code>
                                {
                                    "id": integer,
                                    "name": string,
                                    "description": string,
                                    "image": string
                                }
                            </code>
                        </pre>
                    </div>
                </td>
            </tr>
            <tr>
                <td>Delete course</td>
                <td><b>DELETE</b> /api/course/{id}: integer</td>
                <td>
                    <div>
                        <b>Request:</b>
                        <pre>
                            <code>
                                (empty)
                            </code>
                        </pre>
                    </div>
                    <div>
                        <b>Response:</b>
                        <pre>
                            <code>
                                (empty)
                            </code>
                        </pre>
                    </div>
                </td>
            </tr>
            <tr>
                <td>Health check</td>
                <td><b>GET</b> /api/health</td>
                <td>
                    <div><b>Request:</b>
                        <pre><code>(empty)</code></pre>
                    </div>
                    <div><b>Response:</b>
                        <pre><code>(empty)</code></pre>
                    </div>
                </td>
            </tr>
            <tr>
                <td>API documentation</td>
                <td><b>GET</b> /api/help</td>
                <td>
                    <div><b>Request:</b>
                        <pre><code>(empty)</code></pre>
                    </div>
                    <div><b>Response:</b>
                        <pre><code>help view</code></pre>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</body>

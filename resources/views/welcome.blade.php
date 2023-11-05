<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Web Crawler</title>
        <style>
            .styled-table {
                border-collapse: collapse;
                margin: 25px 0;
                font-size: 0.9em;
                font-family: sans-serif;
                min-width: 400px;
            }
        </style>
    </head>
    <body class="antialiased">
        <div>
            <div>
                <h3>Crawl another website</h3>
                <div>
                    <form action="/crawl" method="POST">
                        @csrf
                        <input type="text" placeholder="Enter url" name="url" required style="width: 45%!important;" value="{{ old('url') }}">
                        <input type="number" min="4" max="6" value="6" name="level">
                        <button>Submit</button>
                    </form>
                    <div>
                        @if($errors != "")
                            <div style="color: red;">
                                {{ implode($errors) }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            <br />
            <hr />
            <div>
                <h2>
                    Results for crawling: "{{ $website }}"
                </h2>
            </div>
            <div>
                <table class="styled-table" style=" min-height: 200px;!important;">
                    <tbody>
                    @foreach($results as $key => $value)
                        <tr>
                            <td>
                                <b>{{ $key  }}</b>
                            </td>
                            <td style="float: right!important;">
                                {{ $value }}
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <br />
            <div>
                <h2>
                    HTTP status codes
                </h2>
                <div>
                    <table class="styled-table">
                        <thead>
                        <tr>
                            <th style="float: left!important;">Page</th>
                            <th>Code</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($links as $link => $code)
                            <tr>
                                <td>
                                    <a href="{{ $link }}" target="_blank">
                                        {{ $link }}
                                    </a>
                                </td>
                                <td style="float: right!important;">
                                    {{ $code }}
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <div></div>
    </body>
</html>

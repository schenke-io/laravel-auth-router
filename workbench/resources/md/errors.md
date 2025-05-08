## Errors

The package handles two types of errors differently:

1) setup errors the developer can handle
2) runtime errors which influence the user experience

### Setup errors

The setup errors are shown in the `/login` selector page. 
If you have any errors with your setup, you can fix them in `config/services.php`.
Its mainly missing keys, or missing provider names in `config/services.php`.

### Runtime errors

The runtime errors are stored in a session and can be handled by the app.

| key                    | value                              | language       |
|------------------------|------------------------------------|----------------|
| authRouterErrorInfo    | user message of the error          | localised      |
| authRouterErrorMessage | exception text of the provider/code | english mainly |

The error page could look like:

```bladehtml
<h3>
    {{session('authRouterErrorInfo')}}
</h3>
<p>
    {{session('authRouterErrorMessage')}}
</p>
```
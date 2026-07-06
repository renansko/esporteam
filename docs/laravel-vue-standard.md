# Laravel + Vue Standard

## Frontend

- Pages/views compose feature components and call composables.
- Components stay presentation-focused and emit actions.
- Composables own reusable UI/application behavior and expose plain functions/refs.
- Services own HTTP calls and translate API payload shape.
- Stores hold cross-screen state only.

Example flow:

```text
View -> composable -> service -> API
View -> component -> emit -> composable action
```

## Backend

- Form requests validate and authorize input.
- Controllers translate HTTP to application calls and resources.
- Services own business rules, transactions, and external service calls.
- Models own persistence relationships, casts, scopes, and small domain helpers.
- Resources shape API output.

Example flow:

```text
Route -> Controller -> Request -> Service -> Model -> Resource
```

Controllers should stay thin. If a controller method needs branching business rules, move that logic to a service.

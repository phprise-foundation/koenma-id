# GUIDE.md — Como aplicar o OTAKU Manifesto

> Guia prático de aplicação das regras do [`PHILOSOPHY.md`](PHILOSOPHY.md), com
> exemplos claros. **Quando usar:** quando houver dúvida ou indecisão sobre como
> implementar uma regra. Se não houver exemplo claro aqui, decidimos juntos e
> adicionamos o exemplo a este documento.

---

## Regra 1 — Um nível de indentação por método

**O que significa:** um método não pode ter `if` dentro de `if`, `foreach` dentro
de `if`, etc. Extraia o corpo interno para outro método.

**Errado:**

```php
public function resolve(RoutePath $path): Route
{
    foreach ($this->routes as $route) {
        if ($route->path()->equals($path)) {
            return $route;
        }
    }
    throw new RouteNotFoundException();
}
```

**Certo:**

```php
public function resolve(RoutePath $path): Route
{
    return $this->find($path) ?? throw new RouteNotFoundException();
}

private function find(RoutePath $path): ?Route
{
    return $this->routes->firstMatching($path);
}
```

---

## Regra 2 — Sem `else`

**O que significa:** use early return ou polimorfismo. O `else` quase sempre
esconde uma decisão que poderia ser um retorno antecipado.

**Errado:**

```php
if ($service->isAvailable()) {
    return $this->forward($request, $service);
} else {
    throw new ServiceUnavailableException();
}
```

**Certo:**

```php
if (! $service->isAvailable()) {
    throw new ServiceUnavailableException();
}
return $this->forward($request, $service);
```

---

## Regra 3 — Envolva primitivos e strings

**O que significa:** um `string` que representa um nome de serviço não é um
`string` qualquer. Envolva-o em um value object que valida e dá significado.

**Errado:**

```php
public function register(string $name, string $host): void
```

**Certo:**

```php
public function register(ServiceName $name, ServiceHost $host): void
```

Veja `Domain/Service/ServiceName.php` e `Domain/Service/ServiceHost.php` como
referência.

---

## Regra 4 — Coleções de primeira classe

**O que significa:** uma coleção de rotas não é um `array`. É uma classe com
comportamento próprio.

**Errado:**

```php
/** @var Route[] */
private array $routes;

public function find(string $method, string $path): ?Route
{
    foreach ($this->routes as $route) {
        // ...
    }
}
```

**Certo:**

```php
final class RouteMap
{
    /** @param list<Route> $routes */
    public function __construct(private array $routes) {}

    public function resolve(HttpMethod $method, RoutePath $path): Route
    {
        // ...
    }
}
```

Veja `Domain/Route/RouteMap.php` e `Domain/Service/ServiceRegistry.php`.

---

## Regra 5 — Um ponto por linha (Lei de Demeter)

**O que significa:** não encadeie chamadas. `$a->b()->c()->d()` viola a lei.
Atribua a um intermediário.

**Errado:**

```php
return $this->registry->find($name)->host()->value();
```

**Certo:**

```php
$service = $this->registry->find($name);
$host = $service->host();
return $host->value();
```

---

## Regra 6 — Não abrevie

**O que significa:** `$req`, `$svc`, `$cfg` são proibidos. Use `$request`,
`$service`, `$configuration`.

---

## Regra 7 — Entidades pequenas

**O que significa:** no máximo 50 linhas por classe e 10 arquivos por pacote.
Se uma classe cresceu, ela tem mais de uma responsabilidade: divida.

---

## Regra 8 — No máximo 2 variáveis de instância

**O que significa:** uma classe com 3+ propriedades provavelmente agrupa
responsabilidades. Extraia um colaborador.

**Errado:**

```php
final class Service
{
    public function __construct(
        private ServiceName $name,
        private ServiceHost $host,
        private bool $available,
    ) {}
}
```

**Certo:** agrupe o que anda junto em um value object (ex: `ServiceEndpoint`
com `name` + `host`), ou repense a responsabilidade.

---

## Regra 9 — Sem getters/setters/propriedades públicas

**O que significa:** o objeto expõe **comportamento**, não estado. Em vez de
`getName()`, o objeto faz algo com o nome.

**Errado:**

```php
$service->getName();
$service->setHost($host);
```

**Certo:**

```php
$service->matches($name);
$service->relocateTo($host);
```

---

## Convenções de arquivo

```php
<?php

declare(strict_types=1);

namespace Phprise\GroguGateway\Domain\Route;

final class Route
{
}
```

- Primeira linha: `<?php`.
- Terceira linha: `declare(strict_types=1);`.
- Namespace.
- **Nunca** feche com `?>`.
- Sem comentários no código.

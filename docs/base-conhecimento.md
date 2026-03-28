# Base de Conhecimento — Escolinha Pro

## Docker no Windows com Livewire é lento?

**Pergunta:** Por que a navegação no sistema demora ~17 segundos no Docker rodando no Windows, mas outra aplicação Laravel (checklist) no mesmo ambiente não tem esse problema?

**Resposta:**

A lentidão tem duas camadas:

### 1. Bind mount Windows → Docker (afeta qualquer app)

Quando o projeto está em `C:\Users\...`, cada leitura de arquivo PHP passa pela camada de virtualização do Docker Desktop. Uma app simples sem frameworks pesados lê poucos arquivos por request, então o overhead é menos perceptível.

### 2. Livewire amplifica o problema

Livewire 4 com SFC (Single File Components — arquivos `⚡nome.blade.php`) adiciona uma etapa extra: o compilador precisa **ler** o arquivo fonte, **compilar** para uma classe PHP e **gravar** o resultado em `storage/framework/views/livewire/`. São 3 operações de I/O lentas por componente novo.

Além disso, com `opcache.validate_timestamps=1` e `revalidate_freq=0`, o OPcache faz `stat()` em **todos os arquivos cacheados a cada request** para checar se mudaram. Com centenas de arquivos PHP do Laravel e o I/O lento do bind mount Windows, isso sozinho pode somar 15–20 segundos.

### Por que o checklist não tem esse problema

- É uma app Laravel convencional (Controllers + Blade simples), sem compilação de componentes Livewire
- Menos arquivos sendo lidos por request
- Com OPcache aquecido, o overhead total é menor e fica abaixo do limiar perceptível

### Solução

Mover o projeto para o filesystem do WSL2 (ex: `~/projetos/escolinha-pro`). No WSL2 o filesystem é nativo Linux — sem camada de virtualização. Mesmo com Livewire e todos os arquivos do vendor, a leitura é direta e a navegação fica sub-segundo.

Com o projeto no WSL2, é possível usar `opcache.validate_timestamps=1` (mudanças de código refletem imediatamente sem restartar o FPM) sem perda de performance.

---

## Testes Pest apagando o banco de dados de produção no Docker

**Problema:** Após rodar `php artisan test` dentro do container, o banco PostgreSQL ficava vazio — os dados semeados sumiam.

**Causa:** O Docker injeta variáveis de ambiente via `env_file: .env.docker` (ex: `DB_CONNECTION=pgsql`). O Dotenv do Laravel usa `ImmutableRepository`, que **não permite sobrescrever variáveis já definidas no processo**. Por isso, as tags `<env>` do `phpunit.xml` (mesmo com `force="true"`) e o método `getEnvironmentSetUp()` do Laravel **não funcionam** nesse cenário — o `DB_CONNECTION` permanece `pgsql`, e o `RefreshDatabase` apaga e recria o banco PostgreSQL a cada teste.

**Solução:** Sobrescrever `refreshApplication()` no `tests/TestCase.php` para chamar `getEnvironmentSetUp()` manualmente logo após a criação do app, antes do `RefreshDatabase` checar a conexão:

```php
protected function refreshApplication(): void
{
    $this->app = $this->createApplication();
    $this->getEnvironmentSetUp($this->app);
}

protected function getEnvironmentSetUp($app): void
{
    $app['config']->set('database.default', 'sqlite');
    $app['config']->set('database.connections.sqlite', [
        'driver'   => 'sqlite',
        'database' => ':memory:',
        'prefix'   => '',
    ]);
}
```

Isso altera o config **diretamente** (bypassando o Dotenv), então o `RefreshDatabase` usa SQLite in-memory e nunca toca o PostgreSQL.

---

## WithoutModelEvents no DatabaseSeeder quebra o TenantObserver

**Problema:** Após rodar `php artisan db:seed`, as categorias padrão (Sub-7 a Sub-17) não eram criadas para o tenant.

**Causa:** O `DatabaseSeeder` usava o trait `WithoutModelEvents`, que desabilita **todos os model events** durante o seed — incluindo o evento `created` do `Tenant` que dispara o `TenantObserver`. Sem o observer, as categorias padrão nunca eram semeadas.

**Solução:** Remover `use WithoutModelEvents` do `DatabaseSeeder`. O trait existe para evitar efeitos colaterais indesejados, mas neste projeto os observers são parte essencial do setup de dados.

---

## tempnam(): file created in the system's temporary directory

**Problema:** Erro no browser ao acessar qualquer página com Livewire. O Laravel converte o warning do PHP em exceção.

**Causa:** O compilador de views do Livewire 4 usa `tempnam()` para gravar arquivos compilados em `storage/framework/views/livewire/`. Se o processo PHP-FPM (que roda como `www-data`) não tem permissão de escrita nesse diretório, o PHP cria o arquivo no `/tmp` do sistema e emite um warning — que o Laravel eleva a `ErrorException`.

Isso acontece porque os diretórios são criados pelo usuário errado (root ou o usuário do host) e o `www-data` fica sem acesso.

**Solução:**

```bash
# No WSL2
sudo chmod -R 777 storage bootstrap/cache
docker compose exec escolinhapro_app_fpm php artisan view:clear
```

Para garantir no Dockerfile (próximo build):

```dockerfile
RUN mkdir -p storage/framework/views storage/framework/cache \
    storage/framework/sessions storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache
```

# Domain Documentation

This project uses a modular monolith structure. Domain code lives under `api/app/Domains/*` and API routes are split under `api/routes/domains/*.php`.

## Domains

- [Identity](./identity.md)
- [Trackmania](./trackmania.md)
- [Seasons](./seasons.md)
- [Activity](./activity.md)
- [Integrations](./integrations.md)

## Conventions

- Models: `api/app/Domains/<Domain>/Models`
- Services: `api/app/Domains/<Domain>/Services`
- HTTP controllers/requests/resources: `api/app/Domains/<Domain>/Http/*`
- Domain routes: `api/routes/domains/<domain>.php`

Design direction:

- Concrete services with clear responsibilities.
- Incremental extraction and boundary hardening.
- No speculative interface/contract layer unless there is an active multi-implementation need.

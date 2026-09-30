# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

Hoteles independientes y pequeñas cadenas (1–5 hoteles). La web habla a la vez a quien decide la compra (propietario/dirección: resultado, ventas directas, menos carga de recepción) y a quien usa las herramientas cada día (recepción/operaciones: el día a día del turno).

## Product Purpose

Hotel Ads es la consultora de Antonio Soler (Almería) para hoteles independientes: gestión, motor de reservas, asesoría y el producto **/staff**.

**/staff** (antes "Asistente IA" / "Antonio AI") es un servicio que aúna frontdesk y concierge: un asistente de IA que atiende web, WhatsApp y teléfono, más el portal interno del equipo (check-in online, mensajes, bonos regalo, opiniones, clientes VIP, fichajes y turnos, grupos, recomendación de tarifas, precios de la competencia, manuales). Éxito: el hotel vende más directo y la recepción deja de apagar fuegos.

## Positioning

No es un chatbot suelto ni un SaaS genérico: es el mismo sistema que ya funciona a diario en hoteles reales, hecho por alguien que gestiona hoteles, con el asistente de IA y las herramientas del equipo compartiendo un único portal.

## Operating Context

- El portal del equipo se usa en recepción (ordenador) y en el móvil del personal.
- El huésped interactúa por chat web, WhatsApp, teléfono, QR en la habitación, check-in online desde el móvil y mensaje post-estancia para opinar.
- Contratación: demo/reunión vía calendario (https://calendar.app.google/ouMpShqMGVHmtUJU7). No se publican precios.

## Capabilities and Constraints

- Nombre del producto: "/staff". URL: https://hotelads.es/staff (la antigua /asistente-ai redirige 301).
- Sin marcas de PMS, motor de reservas ni proveedores (IA, telefonía, APIs) en textos públicos.
- Stack: Astro 6 + Tailwind v4, despliegue estático con .htaccess (Apache).
- Módulos en producción: asistente web/WhatsApp/voz (ES/EN/FR/DE), consulta de reserva por WhatsApp, bandeja de conversaciones con toma de control humana, envíos masivos WhatsApp, check-in online + firma digital, conserje por QR, bonos regalo (venta con pago online, envío, canje y vigencia), opiniones internas post-estancia, clientes VIP, fichajes (también por WhatsApp) e incidencias, cuadrante de turnos multicentro con costes, grupos (seguimiento y propuestas), recomendación diaria de tarifas, comparador de precios de la competencia, manuales.
- Crear reservas desde el panel: aún no en producción.

## Brand Commitments

- Marca: "Hotel Ads" / "HotelAds". Lema existente: "Making Things Happen". El portal lleva "Panel construido por Hotelads".
- Capturas del producto siempre anonimizadas: sin datos de huéspedes, empleados ni nombre del hotel cliente.

## Evidence on Hand

- Capturas reales anonimizadas del portal en producción (hotel ficticio, datos de demostración).
- No hay testimonios, logos de clientes, cifras de resultados ni precios publicables: no inventarlos. Ningún cliente se nombra.

## Product Principles

1. Enseñar el producto funcionando, no prometerlo.
2. Hablar de funciones y resultados, nunca de tecnología ni proveedores.
3. Un único sistema para huésped y equipo.
4. Respeto por los datos: todo lo que se muestra es ficticio.

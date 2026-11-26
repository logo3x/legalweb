# Lente de Producto
Score: 5 | Vote: FUND IF

## Qué es fuerte
- **Existe una frase única y clara:** "te avisamos por correo cuando se mueve tu proceso". El dolor es real y diario (revisar proceso por proceso) y el resultado llega solo, sin que el abogado haga nada.
- **Primeros cinco minutos bien pensados:** registro con Google en 60 segundos, sin tarjeta, importación por radicado o Excel que trae despacho, juez y actuaciones.
- **Oficio en lo que no se ve:** ~230 pruebas, auditorías OWASP corregidas, seudonimización antes de enviar datos a la IA.
- **El enlace vigilancia → vencimiento automático** es coherente: convierte un aviso en una fecha que no se puede pasar.

## Qué lo mataría (los 3 principales, el más peligroso primero)
1. **La promesa central descansa sobre un cimiento ajeno y frágil.** Las fuentes son APIs no documentadas ni contractuales que ya cambiaron una vez. Una actuación no detectada es un término vencido: el producto falla justo en el único momento que importa. Además, muchas actuaciones llegan con anotación vacía y sin el documento, así que el correo dice "algo pasó" y el abogado igual debe ir a la página oficial. La experiencia se rompe en la costura principal.
2. **Falta de foco.** Siete módulos (vigilancia, términos, portal, IA, facturación, analítica, campañas, n8n, marca blanca) sostenidos por una sola persona. Ninguno puede ser brillante; todos compiten por atención con lo único que debe ser infalible.
3. **Cortes de esquina en lo que el cliente termina sintiendo.** IA sobre niveles gratuitos que cambian sin aviso (borradores de demanda de calidad variable), sincronización nocturna disparada por HTTP en hosting compartido, sin integración continua. Además, tras la prueba de 3 meses el usuario cae a 5 casos y sincronización semanal: la degradación es el momento de mayor fricción.

## Las preguntas que le haría al fundador
- ¿Qué porcentaje de actuaciones nuevas se detecta dentro de 24 horas, y cómo lo mide? ¿Cuántas se han perdido?
- Cuando la sincronización falla o la fuente cambia, ¿el abogado se entera, o recibe silencio que parece "sin novedades"?
- ¿Qué fracción de los avisos llega con anotación vacía?
- Si solo pudiera conservar dos módulos, ¿cuáles? ¿Cuáles usan realmente los clientes?
- ¿Cuántos se registran, cuántos importan un proceso el primer día y cuántos pagan al terminar la prueba?
- ¿Qué pasa con la vigilancia por cédula ahora que solo se busca por nombre (homónimos, falsos positivos)?

## Si FUND IF: las condiciones
1. **Medir y publicar la confiabilidad de la vigilancia** (tasa de detección, latencia) y avisar explícitamente al usuario cuando una sincronización no se completó.
2. **Congelar funciones nuevas** y retirar u ocultar los módulos sin uso demostrado; la vigilancia y los términos son el producto.
3. **Sacar la tarea nocturna del hosting compartido** a una infraestructura con monitoreo y reintentos.
4. **IA de pago o IA fuera:** no entregar borradores jurídicos sobre modelos gratuitos inestables.
5. **Mostrar datos de uso reales** (activación y conversión tras la prueba) antes de cualquier desembolso.

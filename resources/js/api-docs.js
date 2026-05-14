import SwaggerUI from 'swagger-ui-dist/swagger-ui-es-bundle.js';
import 'swagger-ui-dist/swagger-ui.css';

const swaggerContainer = document.getElementById('swagger-ui');

if (swaggerContainer) {
    SwaggerUI({
        dom_id: '#swagger-ui',
        url: swaggerContainer.dataset.openapiUrl || '/openapi.json',
        deepLinking: true,
        persistAuthorization: true,
        displayRequestDuration: true,
        docExpansion: 'list',
        defaultModelsExpandDepth: 1,
        tryItOutEnabled: true,
    });
}

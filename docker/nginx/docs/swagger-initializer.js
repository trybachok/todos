// A separate file instead of an inline script: the CSP forbids inline scripts.
window.ui = SwaggerUIBundle({
  url: 'openapi.yaml',
  dom_id: '#swagger-ui',
  validatorUrl: null,
});

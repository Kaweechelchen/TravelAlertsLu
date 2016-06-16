var koa = require('koa');
var app = koa();

app.use(function *(){
    this.body = 'Hello wurld ssss';
});

app.listen(8080);

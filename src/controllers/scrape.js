'use strict';
const views = require('co-views');
const parse = require('co-body');
var rp = require('request-promise');
const issues = [];

const render = views(__dirname + '/../views', {
    map: { html: 'swig' }
});

var fetch = function fetch(url) {
    return rp(url);
}

module.exports.fetcha = function() {
    //return fetch('http://mobile.cfl.lu/bin/help.exe/enl?tpl=rss_feed_global');
    console.log('test');
}

module.exports.home = function *home(ctx) {
    this.body = yield render('list', { 'messages': messages });
};

module.exports.list = function *list() {

    console.log('loading');
    this.body = yield fetch('http://mobile.cfl.lu/bin/help.exe/enl?tpl=rss_feed_global');
    issues.push('hello');
    console.log(issues);
};

module.exports.fetch = function *fetch(id) {
    const message = messages[id];
    if (!message) {
        this.throw(404, 'message with id = ' + id + ' was not found');
    }
    this.body = yield message;
};

module.exports.create = function *create() {
    const message = yield parse(this);
    const id = messages.push(message) - 1;
    message.id = id;
    this.redirect('/');
};

const asyncOperation = () => callback =>
    setTimeout(
        () => callback(null, 'this was loaded asynchronously and it took 2 seconds to complete'),
        2000);

const returnsPromise = () =>
    new Promise((resolve, reject) =>
        setTimeout(() => resolve('promise resolved after 2 seconds'), 2000));

module.exports.delay = function *delay() {
    this.body = yield asyncOperation();
};

module.exports.promise = function *promise() {
    this.body = yield returnsPromise();
};

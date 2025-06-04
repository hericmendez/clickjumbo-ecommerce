const API_URL = 'http://clickjumbo.local/wp-json/clickjumbo/v1';
const route = API_URL +'/products-by-prison-admin'
console.log("route ==> ", route);
const res = await fetch(route);
console.log("res ==> ", res);
const json = await res.json();
console.log('resposta:', res.status, json);
 
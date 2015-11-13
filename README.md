Simple HTTP Mapper
==================

It's a simple async HTTP server (with async HTTP request to another REST API server)

Example:

You are getting an array of users from another service
and you need to response a JSON

```
----> request
GET /feed?type=1

-> Getting an array(1,2,...n) of new posts
-> Trying to get post from hot cache in memory else create an async request to another service api.local/post/1
```

#### <--- response

```json
[
  {
    id: 1,
    title: "..",
    ...
  },
  {
    id: 2,
    title: "..",
    ...
  },
  ....
  {
    id: n,
    title: "..",
    ...
  },
]
```

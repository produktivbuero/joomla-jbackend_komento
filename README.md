This is a jBackend plugin to get Komento comments in Joomla!

# jBackend
jBackend is a Joomla extension that gives you all the power of Joomla CMS through an extensible and pluggable set of REST API. 
https://extensions.joomla.org/extension/jbackend/


# Komento
Komento is an advanced comment extension that allows your website visitors to comment on articles, blogs and product pages. 
https://extensions.joomla.org/extension/komento/

# API methods

## Get a list of comment

```<end-point>/get/komento/comments```

### Parameter
* component [int] (optional, default: all)
* cid [int] (optional)
* parent_id (optional)
* sticked [int] (optional, default: all)
* limit [int] (optional, default: 20, maximum: 20)
* offset [int] (optional, default: 0)

### Response
```
{
  "status": "ok",
  "total": 20,
  "limit": 20,
  "offset": 0,
  "pages_current": 1,
  "pages_total": 1,
  "comments": [
    {
      "id": "1",
      "component": "com_content",
      "cid": "1",
      "title": "Title",
      "comment": "Text",
      "name": "Author",
      "email": "mail@domain.de",
      "url": "https://www.domain.de",
      "created_by": "1",
      "created_date": "0000-00-00T00:00:00+00:00",
      "modified_date": "0000-00-00T00:00:00+00:00",
      "published_date": "0000-00-00T00:00:00+00:00",
      "unpublished_date": "0000-00-00T00:00:00+00:00",
      "state": "1",
      "sticked": "0",
      "parent_id": "0",
      "child_total": "0"
    },
    {
        ...
    }
  ]
```

## Get a single comment

```<end-point>/get/komento/comment?id=0```

### Parameter

* id [int] (mandatory)

### Response

```
{
  "status": "ok",
  "id": "1",
  "component": "com_content",
  "cid": "1",
  "title": "Titel",
  "comment": "1",
  "name": "Autor",
  "email": "mail@domain.de",
  "url": "https://www.domain.de",
  "created_by": "1",
  "created_date": "0000-00-00T00:00:00+00:00",
  "modified_date": "0000-00-00T00:00:00+00:00",
  "published_date": "0000-00-00T00:00:00+00:00",
  "unpublished_date": "0000-00-00T00:00:00+00:00",
  "state": "1",
  "sticked": "0",
  "parent_id": "0",
  "child_total": "0"
}
```

# Plugin Settings
* Sort comments: Newest, Oldest, Random
* Filter comments by state: Published, Unpublished
* Maximum of items to return (the upper value for limit)

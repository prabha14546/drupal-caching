# Simple default VCL.
#ddev-generated
# For a more advanced example see https://github.com/mattiasgeniar/varnish-6.0-configuration-templates
vcl 4.1;

import std;

# Define an ACL for allowed purge sources.
acl purge {
  "localhost";
  "varnish";
  "172.21.0.0"/16;  // Allow internal Docker network
  "172.18.0.0"/16;
}

backend default {
  .host = "web";
  .port = "80";
}

sub vcl_recv {
  # Allow PURGE & BAN only from trusted sources
  if (req.method == "BAN") {
    if (!client.ip ~ purge) {
        return(synth(405, "Not Allowed"));
    }
    ban("req.http.host == " + req.http.host + " && req.url == " + req.url);
    return(synth(200, "Ban added"));
  }
}

sub vcl_deliver {
  # Add a header to indicate if the response is cached
  if (obj.hits > 0) {
    set resp.http.X-Cache = "HIT";
  } else {
    set resp.http.X-Cache = "MISS";
  }
}

sub vcl_synth {
  # Provide messages for errors
  if (resp.status == 405) {
    set resp.http.Content-Type = "text/plain; charset=utf-8";
    synthetic("405 Not Allowed.");
    return (deliver);
  }
  if (resp.status == 400) {
    set resp.http.Content-Type = "text/plain; charset=utf-8";
    synthetic("400 Bad Request.");
    return (deliver);
  }
}

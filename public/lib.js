! function(e, t) {
    "use strict";
    "function" == typeof define && define.amd ? define(["angular"], t) : e.hasOwnProperty("angular") ? t(e.angular) : "object" == typeof exports && (module.exports = t(require("angular")))
}(this, function(e) {
    "use strict";

    function t(e, t) {
        var n;
        try {
            n = e[t]
        } catch (r) {
            n = !1
        }
        if (n) {
            var o = "__" + Math.round(1e7 * Math.random());
            try {
                e[t].setItem(o, o), e[t].removeItem(o, o)
            } catch (r) {
                n = !1
            }
        }
        return n
    }

    function n(n) {
        var r = t(window, n);
        return function() {
            var o = "ngStorage-";
            this.setKeyPrefix = function(e) {
                if ("string" != typeof e) throw new TypeError("[ngStorage] - " + n + "Provider.setKeyPrefix() expects a String.");
                o = e
            };
            var i = e.toJson,
                a = e.fromJson;
            this.setSerializer = function(e) {
                if ("function" != typeof e) throw new TypeError("[ngStorage] - " + n + "Provider.setSerializer expects a function.");
                i = e
            }, this.setDeserializer = function(e) {
                if ("function" != typeof e) throw new TypeError("[ngStorage] - " + n + "Provider.setDeserializer expects a function.");
                a = e
            }, this.supported = function() {
                return !!r
            }, this.get = function(e) {
                return r && a(r.getItem(o + e))
            }, this.set = function(e, t) {
                return r && r.setItem(o + e, i(t))
            }, this.remove = function(e) {
                r && r.removeItem(o + e)
            }, this.$get = ["$rootScope", "$window", "$log", "$timeout", "$document", function(r, u, s, c, l) {
                var f, h, g = o.length,
                    d = t(u, n),
                    p = d || (s.warn("This browser does not support Web Storage!"), {
                        setItem: e.noop,
                        getItem: e.noop,
                        removeItem: e.noop
                    }),
                    v = {
                        $default: function(t) {
                            for (var n in t) e.isDefined(v[n]) || (v[n] = e.copy(t[n]));
                            return v.$sync(), v
                        },
                        $reset: function(e) {
                            for (var t in v) "$" === t[0] || delete v[t] && p.removeItem(o + t);
                            return v.$default(e)
                        },
                        $sync: function() {
                            for (var e, t = 0, n = p.length; n > t; t++)(e = p.key(t)) && o === e.slice(0, g) && (v[e.slice(g)] = a(p.getItem(e)))
                        },
                        $apply: function() {
                            var t;
                            if (h = null, !e.equals(v, f)) {
                                t = e.copy(f), e.forEach(v, function(n, r) {
                                    e.isDefined(n) && "$" !== r[0] && (p.setItem(o + r, i(n)), delete t[r])
                                });
                                for (var n in t) p.removeItem(o + n);
                                f = e.copy(v)
                            }
                        },
                        $supported: function() {
                            return !!d
                        }
                    };
                return v.$sync(), f = e.copy(v), r.$watch(function() {
                    h || (h = c(v.$apply, 100, !1))
                }), u.addEventListener && u.addEventListener("storage", function(t) {
                    if (t.key) {
                        var n = l[0];
                        n.hasFocus && n.hasFocus() || o !== t.key.slice(0, g) || (t.newValue ? v[t.key.slice(g)] = a(t.newValue) : delete v[t.key.slice(g)], f = e.copy(v), r.$apply())
                    }
                }), u.addEventListener && u.addEventListener("beforeunload", function() {
                    v.$apply()
                }), v
            }]
        }
    }
    return e = e && e.module ? e : window.angular, e.module("ngStorage", []).provider("$localStorage", n("localStorage")).provider("$sessionStorage", n("sessionStorage"))
}),
function(e) {
    e.module("authClient.config", []).value("authClient.config", {
        debug: !0
    }), e.module("authClient.services", ["ngStorage"]), e.module("authClient", ["authClient.config", "authClient.services"])
}(angular), angular.module("authClient.services").service("authenticate", ["$localStorage", "$http", "$location", "$q", "$window", "baseUrl", function(e, t, n, r, o, i) {
    var a = this;
    this.getToken = function() {
        return e.jwt
    }, this.check = function(o) {
        return r(function(r, u) {
            var s = a.getToken();
            s || (s = n.search().jwt, s && (e.jwt = s));
            var c = {
                params: {
                    next: o
                },
                withCredentials: !1
            };
            s = a.getToken(), s && (c.params.jwt = s), t.get(i.getBaseUrl() + "/user/check", c).then(function(t) {
                var n = t.data;
                n.authenticated ? r({
                    token: s,
                    profile: n.profile
                }) : (delete e.jwt, u(n.providers))
            })
        })
    }, this.login = function(e, t) {
        o.open(e, t)
    }, this.logout = function() {
        a.getToken() && (delete e.jwt, n.search("jwt", null))
    }
}]), angular.module("authClient.services").service("authorize", ["$http", "$q", "baseUrl", function(e, t, n) {
    this.check = function(r, o) {
        return t(function(t, i) {
            var a = {
                params: {
                    jwt: r,
                    service: o
                },
                withCredentials: !1
            };
            e.get(n.getBaseUrl() + "/user/authorize", a).then(function(e) {
                var n = e.data;
                t(n)
            }, function() {
                i({})
            })
        })
    }
}]), angular.module("authClient.services").service("baseUrl", function() {
    var e = "//cors-anywhere.herokuapp.com/next.openspending.org";
    this.setBaseUrl = function(t) {
        e = t
    }, this.getBaseUrl = function() {
        return e
    }
});
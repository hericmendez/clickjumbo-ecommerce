const response = {
    "namespace": "clickjumbo/v1",
    "routes": {
        "/clickjumbo/v1": {
            "namespace": "clickjumbo/v1",
            "methods": [
                "GET"
            ],
            "endpoints": [
                {
                    "methods": [
                        "GET"
                    ],
                    "args": {
                        "namespace": {
                            "default": "clickjumbo/v1",
                            "required": false
                        },
                        "context": {
                            "default": "view",
                            "required": false
                        }
                    }
                }
            ],
            "_links": {
                "self": [
                    {
                        "href": "https://clickjumbo.com.br/wp-json/clickjumbo/v1"
                    }
                ]
            }
        },
        "/clickjumbo/v1/check-health": {
            "namespace": "clickjumbo/v1",
            "methods": [
                "GET"
            ],
            "endpoints": [
                {
                    "methods": [
                        "GET"
                    ],
                    "args": []
                }
            ],
            "_links": {
                "self": [
                    {
                        "href": "https://clickjumbo.com.br/wp-json/clickjumbo/v1/check-health"
                    }
                ]
            }
        },
        "/clickjumbo/v1/orders-health": {
            "namespace": "clickjumbo/v1",
            "methods": [
                "GET"
            ],
            "endpoints": [
                {
                    "methods": [
                        "GET"
                    ],
                    "args": []
                }
            ],
            "_links": {
                "self": [
                    {
                        "href": "https://clickjumbo.com.br/wp-json/clickjumbo/v1/orders-health"
                    }
                ]
            }
        },
        "/clickjumbo/v1/reset-data": {
            "namespace": "clickjumbo/v1",
            "methods": [
                "POST"
            ],
            "endpoints": [
                {
                    "methods": [
                        "POST"
                    ],
                    "args": []
                }
            ],
            "_links": {
                "self": [
                    {
                        "href": "https://clickjumbo.com.br/wp-json/clickjumbo/v1/reset-data"
                    }
                ]
            }
        },
        "/clickjumbo/v1/login": {
            "namespace": "clickjumbo/v1",
            "methods": [
                "POST"
            ],
            "endpoints": [
                {
                    "methods": [
                        "POST"
                    ],
                    "args": []
                }
            ],
            "_links": {
                "self": [
                    {
                        "href": "https://clickjumbo.com.br/wp-json/clickjumbo/v1/login"
                    }
                ]
            }
        },
        "/clickjumbo/v1/register": {
            "namespace": "clickjumbo/v1",
            "methods": [
                "POST"
            ],
            "endpoints": [
                {
                    "methods": [
                        "POST"
                    ],
                    "args": []
                }
            ],
            "_links": {
                "self": [
                    {
                        "href": "https://clickjumbo.com.br/wp-json/clickjumbo/v1/register"
                    }
                ]
            }
        },
        "/clickjumbo/v1/product-list": {
            "namespace": "clickjumbo/v1",
            "methods": [
                "GET"
            ],
            "endpoints": [
                {
                    "methods": [
                        "GET"
                    ],
                    "args": []
                }
            ],
            "_links": {
                "self": [
                    {
                        "href": "https://clickjumbo.com.br/wp-json/clickjumbo/v1/product-list"
                    }
                ]
            }
        },
        "/clickjumbo/v1/product-details/(?P<id>\\d+)": {
            "namespace": "clickjumbo/v1",
            "methods": [
                "GET"
            ],
            "endpoints": [
                {
                    "methods": [
                        "GET"
                    ],
                    "args": []
                }
            ]
        },
        "/clickjumbo/v1/validate-cart": {
            "namespace": "clickjumbo/v1",
            "methods": [
                "POST"
            ],
            "endpoints": [
                {
                    "methods": [
                        "POST"
                    ],
                    "args": []
                }
            ],
            "_links": {
                "self": [
                    {
                        "href": "https://clickjumbo.com.br/wp-json/clickjumbo/v1/validate-cart"
                    }
                ]
            }
        },
        "/clickjumbo/v1/validate-payment": {
            "namespace": "clickjumbo/v1",
            "methods": [
                "POST"
            ],
            "endpoints": [
                {
                    "methods": [
                        "POST"
                    ],
                    "args": []
                }
            ],
            "_links": {
                "self": [
                    {
                        "href": "https://clickjumbo.com.br/wp-json/clickjumbo/v1/validate-payment"
                    }
                ]
            }
        },
        "/clickjumbo/v1/validate-shipping": {
            "namespace": "clickjumbo/v1",
            "methods": [
                "POST"
            ],
            "endpoints": [
                {
                    "methods": [
                        "POST"
                    ],
                    "args": []
                }
            ],
            "_links": {
                "self": [
                    {
                        "href": "https://clickjumbo.com.br/wp-json/clickjumbo/v1/validate-shipping"
                    }
                ]
            }
        },
        "/clickjumbo/v1/delete-prison/(?P<slug>[a-zA-Z0-9\\-]+)": {
            "namespace": "clickjumbo/v1",
            "methods": [
                "DELETE"
            ],
            "endpoints": [
                {
                    "methods": [
                        "DELETE"
                    ],
                    "args": []
                }
            ]
        },
        "/clickjumbo/v1/prison-list": {
            "namespace": "clickjumbo/v1",
            "methods": [
                "GET"
            ],
            "endpoints": [
                {
                    "methods": [
                        "GET"
                    ],
                    "args": []
                }
            ],
            "_links": {
                "self": [
                    {
                        "href": "https://clickjumbo.com.br/wp-json/clickjumbo/v1/prison-list"
                    }
                ]
            }
        },
        "/clickjumbo/v1/prison-list-full": {
            "namespace": "clickjumbo/v1",
            "methods": [
                "GET"
            ],
            "endpoints": [
                {
                    "methods": [
                        "GET"
                    ],
                    "args": []
                }
            ],
            "_links": {
                "self": [
                    {
                        "href": "https://clickjumbo.com.br/wp-json/clickjumbo/v1/prison-list-full"
                    }
                ]
            }
        },
        "/clickjumbo/v1/prison-details/(?P<slug>[a-zA-Z0-9-_]+)": {
            "namespace": "clickjumbo/v1",
            "methods": [
                "GET"
            ],
            "endpoints": [
                {
                    "methods": [
                        "GET"
                    ],
                    "args": []
                }
            ]
        },
        "/clickjumbo/v1/register-prison": {
            "namespace": "clickjumbo/v1",
            "methods": [
                "POST"
            ],
            "endpoints": [
                {
                    "methods": [
                        "POST"
                    ],
                    "args": []
                }
            ],
            "_links": {
                "self": [
                    {
                        "href": "https://clickjumbo.com.br/wp-json/clickjumbo/v1/register-prison"
                    }
                ]
            }
        },
        "/clickjumbo/v1/update-prison/(?P<slug>[a-zA-Z0-9\\-]+)": {
            "namespace": "clickjumbo/v1",
            "methods": [
                "PUT"
            ],
            "endpoints": [
                {
                    "methods": [
                        "PUT"
                    ],
                    "args": []
                }
            ]
        },
        "/clickjumbo/v1/calculate-shipping": {
            "namespace": "clickjumbo/v1",
            "methods": [
                "POST"
            ],
            "endpoints": [
                {
                    "methods": [
                        "POST"
                    ],
                    "args": []
                }
            ],
            "_links": {
                "self": [
                    {
                        "href": "https://clickjumbo.com.br/wp-json/clickjumbo/v1/calculate-shipping"
                    }
                ]
            }
        },
        "/clickjumbo/v1/orders/(?P<id>\\d+)/status": {
            "namespace": "clickjumbo/v1",
            "methods": [
                "POST"
            ],
            "endpoints": [
                {
                    "methods": [
                        "POST"
                    ],
                    "args": []
                }
            ]
        },
        "/clickjumbo/v1/orders/(?P<id>\\d+)": {
            "namespace": "clickjumbo/v1",
            "methods": [
                "DELETE",
                "GET"
            ],
            "endpoints": [
                {
                    "methods": [
                        "DELETE"
                    ],
                    "args": []
                },
                {
                    "methods": [
                        "GET"
                    ],
                    "args": []
                }
            ]
        },
        "/clickjumbo/v1/export-orders": {
            "namespace": "clickjumbo/v1",
            "methods": [
                "GET"
            ],
            "endpoints": [
                {
                    "methods": [
                        "GET"
                    ],
                    "args": []
                }
            ],
            "_links": {
                "self": [
                    {
                        "href": "https://clickjumbo.com.br/wp-json/clickjumbo/v1/export-orders"
                    }
                ]
            }
        },
        "/clickjumbo/v1/generate-boleto": {
            "namespace": "clickjumbo/v1",
            "methods": [
                "POST"
            ],
            "endpoints": [
                {
                    "methods": [
                        "POST"
                    ],
                    "args": []
                }
            ],
            "_links": {
                "self": [
                    {
                        "href": "https://clickjumbo.com.br/wp-json/clickjumbo/v1/generate-boleto"
                    }
                ]
            }
        },
        "/clickjumbo/v1/generate-pix": {
            "namespace": "clickjumbo/v1",
            "methods": [
                "POST"
            ],
            "endpoints": [
                {
                    "methods": [
                        "POST"
                    ],
                    "args": []
                }
            ],
            "_links": {
                "self": [
                    {
                        "href": "https://clickjumbo.com.br/wp-json/clickjumbo/v1/generate-pix"
                    }
                ]
            }
        },
        "/clickjumbo/v1/orders/(?P<id>\\d+)/receipt": {
            "namespace": "clickjumbo/v1",
            "methods": [
                "GET"
            ],
            "endpoints": [
                {
                    "methods": [
                        "GET"
                    ],
                    "args": []
                }
            ]
        },
        "/clickjumbo/v1/orders": {
            "namespace": "clickjumbo/v1",
            "methods": [
                "GET"
            ],
            "endpoints": [
                {
                    "methods": [
                        "GET"
                    ],
                    "args": []
                }
            ],
            "_links": {
                "self": [
                    {
                        "href": "https://clickjumbo.com.br/wp-json/clickjumbo/v1/orders"
                    }
                ]
            }
        },
        "/clickjumbo/v1/orders/by-user": {
            "namespace": "clickjumbo/v1",
            "methods": [
                "GET"
            ],
            "endpoints": [
                {
                    "methods": [
                        "GET"
                    ],
                    "args": []
                }
            ],
            "_links": {
                "self": [
                    {
                        "href": "https://clickjumbo.com.br/wp-json/clickjumbo/v1/orders/by-user"
                    }
                ]
            }
        },
        "/clickjumbo/v1/process-order": {
            "namespace": "clickjumbo/v1",
            "methods": [
                "POST"
            ],
            "endpoints": [
                {
                    "methods": [
                        "POST"
                    ],
                    "args": []
                }
            ],
            "_links": {
                "self": [
                    {
                        "href": "https://clickjumbo.com.br/wp-json/clickjumbo/v1/process-order"
                    }
                ]
            }
        },
        "/clickjumbo/v1/delete-product/(?P<id>\\d+)": {
            "namespace": "clickjumbo/v1",
            "methods": [
                "DELETE"
            ],
            "endpoints": [
                {
                    "methods": [
                        "DELETE"
                    ],
                    "args": []
                }
            ]
        },
        "/clickjumbo/v1/get-categories": {
            "namespace": "clickjumbo/v1",
            "methods": [
                "GET"
            ],
            "endpoints": [
                {
                    "methods": [
                        "GET"
                    ],
                    "args": []
                }
            ],
            "_links": {
                "self": [
                    {
                        "href": "https://clickjumbo.com.br/wp-json/clickjumbo/v1/get-categories"
                    }
                ]
            }
        },
        "/clickjumbo/v1/categories-full": {
            "namespace": "clickjumbo/v1",
            "methods": [
                "GET"
            ],
            "endpoints": [
                {
                    "methods": [
                        "GET"
                    ],
                    "args": []
                }
            ],
            "_links": {
                "self": [
                    {
                        "href": "https://clickjumbo.com.br/wp-json/clickjumbo/v1/categories-full"
                    }
                ]
            }
        },
        "/clickjumbo/v1/taxonomies": {
            "namespace": "clickjumbo/v1",
            "methods": [
                "GET"
            ],
            "endpoints": [
                {
                    "methods": [
                        "GET"
                    ],
                    "args": []
                }
            ],
            "_links": {
                "self": [
                    {
                        "href": "https://clickjumbo.com.br/wp-json/clickjumbo/v1/taxonomies"
                    }
                ]
            }
        },
        "/clickjumbo/v1/export-products-csv": {
            "namespace": "clickjumbo/v1",
            "methods": [
                "GET"
            ],
            "endpoints": [
                {
                    "methods": [
                        "GET"
                    ],
                    "args": []
                }
            ],
            "_links": {
                "self": [
                    {
                        "href": "https://clickjumbo.com.br/wp-json/clickjumbo/v1/export-products-csv"
                    }
                ]
            }
        },
        "/clickjumbo/v1/register-category": {
            "namespace": "clickjumbo/v1",
            "methods": [
                "POST"
            ],
            "endpoints": [
                {
                    "methods": [
                        "POST"
                    ],
                    "args": []
                }
            ],
            "_links": {
                "self": [
                    {
                        "href": "https://clickjumbo.com.br/wp-json/clickjumbo/v1/register-category"
                    }
                ]
            }
        },
        "/clickjumbo/v1/save-product": {
            "namespace": "clickjumbo/v1",
            "methods": [
                "POST",
                "PUT"
            ],
            "endpoints": [
                {
                    "methods": [
                        "POST",
                        "PUT"
                    ],
                    "args": []
                }
            ],
            "_links": {
                "self": [
                    {
                        "href": "https://clickjumbo.com.br/wp-json/clickjumbo/v1/save-product"
                    }
                ]
            }
        },
        "/clickjumbo/v1/users": {
            "namespace": "clickjumbo/v1",
            "methods": [
                "GET"
            ],
            "endpoints": [
                {
                    "methods": [
                        "GET"
                    ],
                    "args": []
                }
            ],
            "_links": {
                "self": [
                    {
                        "href": "https://clickjumbo.com.br/wp-json/clickjumbo/v1/users"
                    }
                ]
            }
        }
    },
    "_links": {
        "up": [
            {
                "href": "https://clickjumbo.com.br/wp-json/"
            }
        ]
    }
}
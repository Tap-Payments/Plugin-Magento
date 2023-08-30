var config = {
    paths: {
        'goSell': 'https://secure.gosell.io/js/sdk/tap.min',
        'goSellJs' : 'https://goselljslib.b-cdn.net/v1.6.1/js/gosell'
    },
    urlArgs: 'bust=' + (new Date()).getTime()// Disable require js cache
};
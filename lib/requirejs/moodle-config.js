var require = {
    baseUrl : '[BASEURL]',

    paths: {
        jquery: '[JSURL]lib/jquery/jquery-1.11.1.min',
        jqueryprivate: '[JSURL]lib/requirejs/jquery-private'
    },

    // Custom jquery config map.
    map: {
      // '*' means all modules will get 'jqueryprivate'
      // for their 'jquery' dependency.
      '*': { jquery: 'jqueryprivate' },

      // 'jquery-private' wants the real jQuery module
      // though. If this line was not here, there would
      // be an unresolvable cyclic dependency.
      jqueryprivate: { jquery: 'jquery' }
    }
};

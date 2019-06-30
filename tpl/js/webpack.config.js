// https://webpack.js.org/configuration/
// TODO add https://github.com/roman01la/webpack-closure-compiler in production mode + css minifier

const path = require("path");
//const webpack = require('webpack');
//const UglifyJsPlugin = require('uglifyjs-webpack-plugin')

module.exports = {
    mode: "production",
    //mode: "development",
    target: "web",

    entry: "./index.ts",
    output: {
        filename: "bundle.js",
        path: __dirname
    },

    // Enable sourcemaps for debugging webpack's output.
    devtool: "source-map",

    resolve: {
        // Add '.ts' and '.tsx' as resolvable extensions.
        extensions: [".ts", ".tsx", ".js", ".json"]
    },

    module: {
        rules: [
            // All files with a '.ts' or '.tsx' extension will be handled by 'awesome-typescript-loader'.
            {
                test: /\.tsx?$/,
                //loader: "awesome-typescript-loader?presets[]=configFileName!tsconfig.json" // file not found
                // use babel if we want to compile to ES5: https://gist.github.com/c9s/8e2e621d6cfc4e7f8e778d9a592e7f1b
                //loader: "awesome-typescript-loader"
                loader: "awesome-typescript-loader?configFileName=./tsconfig.json"
                /*
                include: [
                    path.resolve(__dirname, "public")
                ],
                exclude: [
                    path.resolve(__dirname, "node_modules")
                ]
                */
            },

            // All output '.js' files will have any sourcemaps re-processed by 'source-map-loader'.
            { enforce: "pre", test: /\.js$/, loader: "source-map-loader" }
        ]
    },

    // When importing a module whose path matches one of the following, just
    // assume a corresponding global variable exists and use that instead.
    // This is important because it allows us to avoid bundling all of our
    // dependencies, which allows browsers to cache those libraries between builds.
    externals: {
        //"react": "React",
        //"react-dom": "ReactDOM"
        "jquery": "jQuery"
    },

    plugins: [
        //new webpack.IgnorePlugin(/^\.\/locale$/, /moment$/)
        //new webpack.optimize.UglifyJsPlugin()
    ]
};
const path = require('path');

module.exports = {
  entry: './src/index.ts',
  output: {
    filename: 'modules.bundle.js',
    path: path.resolve(__dirname, 'dist'),
    library: {
      name: 'LinkHub',
      type: 'umd',
    },
  },
  module: {
    rules: [
      {
        test: /\.tsx?$/,
        use: 'ts-loader',
        exclude: /node_modules/,
      },
      {
        test: /\.json$/,
        type: 'json',
      },
    ],
  },
  resolve: {
    extensions: ['.tsx', '.ts', '.js', '.json'],
    alias: {
      '@divi/module-library': path.resolve(__dirname, 'src/types/divi-module-library'),
    },
  },
  externals: {
    react: 'React',
    'react-dom': 'ReactDOM',
  },
};

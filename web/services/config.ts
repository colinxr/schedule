import { ApiClientConfig } from './core/types';

const config: ApiClientConfig = {
  baseURL: process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api',
  timeout: 10000,
  headers: {
    'X-Requested-With': 'XMLHttpRequest',
  },
};

export default config; 
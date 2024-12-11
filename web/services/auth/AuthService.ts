import { ApiClient } from '../core/ApiClient';
import { ApiResponse } from '../core/types';
import {
  AuthResponse,
  LoginCredentials,
  RegisterData,
  ForgotPasswordData,
  ResetPasswordData,
  User,
} from './types';

export class AuthService extends ApiClient {
  private static instance: AuthService;
  private token: string | null = null;

  private constructor() {
    super({
      baseURL: process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
      },
    });
  }

  public static getInstance(): AuthService {
    if (!AuthService.instance) {
      AuthService.instance = new AuthService();
    }
    return AuthService.instance;
  }

  public setToken(token: string | null): void {
    this.token = token;
    if (token) {
      this.defaultConfig.headers = {
        ...this.defaultConfig.headers,
        'Authorization': `Bearer ${token}`,
      };
    } else {
      delete this.defaultConfig.headers['Authorization'];
    }
  }

  public async login(credentials: LoginCredentials): Promise<ApiResponse<AuthResponse>> {
    const response = await this.post<AuthResponse>('login', credentials);
    this.setToken(response.data.token);
    return response;
  }

  public async register(data: RegisterData): Promise<ApiResponse<AuthResponse>> {
    const response = await this.post<AuthResponse>('register', data);
    this.setToken(response.data.token);
    return response;
  }

  public async logout(): Promise<ApiResponse<void>> {
    const response = await this.post<void>('logout');
    this.setToken(null);
    return response;
  }

  public async forgotPassword(data: ForgotPasswordData): Promise<ApiResponse<void>> {
    return this.post<void>('forgot-password', data);
  }

  public async resetPassword(data: ResetPasswordData): Promise<ApiResponse<void>> {
    return this.post<void>('reset-password', data);
  }

  public async me(): Promise<ApiResponse<User>> {
    return this.get<User>('me');
  }

  public isAuthenticated(): boolean {
    return !!this.token;
  }
} 
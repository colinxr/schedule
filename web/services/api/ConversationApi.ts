import { ApiClient } from '../core/ApiClient';
import { ApiResponse } from '../core/types';
import { AuthService } from '../auth/AuthService';

export interface Conversation {
  id: number;
  artist_id: number;
  client_id: number;
  status: 'pending' | 'active' | 'closed';
  last_message_at: string;
  created_at: string;
  updated_at: string;
  artist: {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
  };
  client: {
    id: number;
    name: string;
    first_name: string;
    last_name: string;
    email: string;
  };
  details: {
    description: string;
    reference_images: string[] | null;
    email: string;
    phone: string | null;
    instagram: string | null;
  };
  messages: {
    id: number;
    content: string;
    sender_type: string;
    sender_id: number;
    created_at: string;
    read_at: string | null;
    data: {
      id: number;
      content: string;
      sender_type: string;
      sender_id: number;
      created_at: string;
      read_at: string | null;
    }[];
  }[];
}

export class ConversationApi extends ApiClient {
  constructor() {
    const authService = AuthService.getInstance();
    super({
      baseURL: process.env.NEXT_PUBLIC_API_URL || 'http://127.0.0.1:8000/api',
      headers: authService['defaultConfig'].headers,
    });

    // Add request interceptor to log headers
    this.addRequestInterceptor((config) => {
      console.log('Request Headers:', config.headers);
      return config;
    });
  }

  public async getConversations(): Promise<ApiResponse<Conversation[]>> {
    return this.get<Conversation[]>('conversations');
  }

  public async getConversation(id: number): Promise<ApiResponse<Conversation>> {
    return this.get<Conversation>(`conversations/${id}`);
  }

  public async createConversation(data: {
    artist_id: number;
    description: string;
    reference_images?: string[];
    email: string;
    phone?: string;
    instagram?: string;
  }): Promise<ApiResponse<Conversation>> {
    return this.post<Conversation>('conversations', data);
  }
} 
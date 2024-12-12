import { ApiClient } from '../core/ApiClient';
import { ApiResponse } from '../core/types';

export interface Conversation {
  id: number;
  status: 'pending' | 'active' | 'closed';
  created_at: string;
  client: {
    id: number;
    name: string;
    details: {
      phone: string | null;
      email: string | null;
      instagram: string | null;
    };
  };
  messages: {
    data: {
      id: number;
      content: string;
      created_at: string;
      read_at: string | null;
      sender_type: string;
      sender_id: number;
    }[];
  };
}

export class ConversationService extends ApiClient {
  private constructor() {
    super({
      baseURL: process.env.NEXT_PUBLIC_API_URL || 'http://127.0.0.1:8000/api',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
      },
    });
  }
  /**
   * Fetch all conversations for the authenticated user
   * Will return different results based on user role (artist/client)
   */
  public async getConversations(): Promise<ApiResponse<Conversation[]>> {
    return this.get<Conversation[]>('/conversations');
  }

  /**
   * Fetch a single conversation by ID
   */
  public async getConversation(id: number): Promise<ApiResponse<Conversation>> {
    return this.get<Conversation>(`/conversations/${id}`);
  }

  /**
   * Create a new conversation with an artist
   */
  public async createConversation(data: {
    artist_id: number;
    description: string;
    reference_images?: string[];
    email: string;
    phone?: string;
    instagram?: string;
  }): Promise<ApiResponse<Conversation>> {
    return this.post<Conversation>('/conversations', data);
  }
} 
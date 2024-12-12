import { ApiClient } from '../core/ApiClient';
import { ApiResponse } from '../core/types';

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
  }[];
}

export class ConversationApi extends ApiClient {
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
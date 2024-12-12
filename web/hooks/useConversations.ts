import { useQuery } from '@tanstack/react-query';
import { ConversationApi, Conversation } from '../services/api/ConversationService';

const api = new ConversationApi({
  baseURL: process.env.NEXT_PUBLIC_API_BASE_URL,
});

export function useConversations() {
  return useQuery<Conversation[], Error>({
    queryKey: ['conversations'],
    queryFn: async () => {
      try {
        const response = await api.getConversations();
        return response.data;
      } catch (error) {
        throw new Error('Failed to fetch conversations');
      }
    },
  });
} 
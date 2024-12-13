import { useQuery } from '@tanstack/react-query';
import { ConversationApi, Conversation } from '../services/api/ConversationApi';

const api = new ConversationApi();

export function useConversations() {
  return useQuery<Conversation[]>({
    queryKey: ['conversations'],
    queryFn: async () => {
      const response = await api.getConversations();
      return response.data;
    },
  });
} 